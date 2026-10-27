<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamAccessCodesTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->create(['school_name' => 'Sekolah QA', 'supervisor_pin_hash' => null]);
    }

    private function exam(User $teacher): ExamSession
    {
        return ExamSession::create(['user_id' => $teacher->id, 'code' => 'AB7K9M', 'title' => 'Ujian', 'school_name' => $teacher->school_name, 'teacher_name' => $teacher->name, 'platform' => 'Google Forms', 'url' => 'https://forms.gle/qa', 'duration_minutes' => 60, 'expires_at' => now()->addHours(2)]);
    }

    private function join(ExamSession $exam): string
    {
        return $this->postJson('/api/student/join', ['code' => $exam->code, 'full_name' => 'Peserta', 'participant_number' => '001'])->assertCreated()->json('participant_token');
    }

    public function test_creation_needs_no_teacher_pin_or_managed_flag_and_codes_are_private(): void
    {
        $u = $this->teacher();
        Sanctum::actingAs($u);
        $r = $this->postJson('/api/exams', ['title' => 'Ujian', 'url' => 'https://forms.gle/qa', 'duration_minutes' => 60, 'valid_hours' => 2])->assertCreated()->assertJsonPath('session.require_managed_lock', false);
        $s = ExamSession::findOrFail($r->json('session.id'));
        $codes = $r->json('action_codes');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codes['exit']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codes['resume']);
        $this->assertNotSame($codes['exit'], $codes['resume']);
        $this->assertNotSame($codes['exit'], DB::table('exam_sessions')->where('id', $s->id)->value('exit_code'));
        $this->assertTrue(Hash::check($codes['exit'], $s->exit_code_hash));
        $this->assertTrue(Hash::check($codes['resume'], $s->resume_code_hash));
        $this->getJson('/api/exams/'.$s->id)->assertJsonPath('action_codes', $codes);
        $this->getJson('/api/exams')->assertJsonMissingPath('action_codes')->assertJsonMissingPath('sessions.0.exit_code')->assertJsonMissingPath('sessions.0.resume_code');
        foreach (['/api/student/lookup', '/api/student/join'] as $path) {
            $this->postJson($path, ['code' => $s->code, 'full_name' => 'Peserta', 'participant_number' => '001'])->assertSuccessful()->assertJsonMissingPath('action_codes')->assertJsonMissingPath('session.exit_code')->assertJsonMissingPath('session.resume_code')->assertJsonMissingPath('session.exit_code_hash')->assertJsonMissingPath('session.resume_code_hash');
        }
        Sanctum::actingAs($this->teacher());
        $this->getJson('/api/exams/'.$s->id)->assertNotFound();
    }

    public function test_exit_and_resume_codes_cannot_be_interchanged_and_clock_is_preserved(): void
    {
        $this->travelTo(now()->startOfSecond());
        $s = $this->exam($this->teacher());
        $token = $this->join($s);
        $this->withToken($token)->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'pinned'])->assertOk();
        $this->travel(10)->minutes();
        $this->withToken($token)->postJson('/api/student/status', ['status' => 'paused', 'lock_mode' => 'none'])->assertOk();
        $this->withToken($token)->postJson('/api/student/authorize', ['pin' => $s->exit_code, 'action' => 'resume'])->assertUnprocessable();
        $this->withToken($token)->postJson('/api/student/authorize', ['pin' => $s->resume_code, 'action' => 'exit'])->assertUnprocessable();
        $this->assertNull(Participant::first()->ended_at);
        $this->withToken($token)->postJson('/api/student/authorize', ['pin' => $s->resume_code, 'action' => 'resume'])->assertOk()->assertJsonPath('timing.remaining_ms', 3000000);
        $this->withToken($token)->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'pinned'])->assertOk()->assertJsonPath('timing.remaining_ms', 3000000);
        $this->withToken($token)->postJson('/api/student/authorize', ['pin' => $s->exit_code, 'action' => 'exit'])->assertOk()->assertJsonPath('participant.status', 'exited');
        $this->travel(5)->minutes();
        $this->withToken($token)->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'pinned'])->assertStatus(409);
        $this->withToken($token)->postJson('/api/student/authorize', ['pin' => $s->resume_code, 'action' => 'resume'])->assertOk()->assertJsonPath('timing.remaining_ms', 2700000);
        $this->assertNull(Participant::first()->ended_at);
        $this->withToken($token)->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'pinned'])->assertOk()->assertJsonPath('timing.remaining_ms', 2700000);
    }

    public function test_expired_exam_gets_new_entry_code_without_losing_participants_or_action_codes(): void
    {
        $this->travelTo(now()->startOfSecond());
        $u = $this->teacher(); $s = $this->exam($u);
        $this->join($s);
        $codes = $s->teacherCodes(); $old = $s->code;
        $s->update(['expires_at' => now()->subMinute()]);
        Sanctum::actingAs($u);
        $r = $this->postJson('/api/exams/'.$s->id.'/reactivate', ['valid_hours' => 3])->assertOk()->assertJsonPath('session.active', true)->assertJsonPath('action_codes', $codes);
        $this->assertNotSame($old, $r->json('session.code'));
        $this->assertSame(now()->addHours(3)->timestamp, $s->fresh()->expires_at->timestamp);
        $this->assertDatabaseCount('participants', 1);
        $this->postJson('/api/student/lookup', ['code' => $old])->assertNotFound();
        $this->postJson('/api/student/lookup', ['code' => $r->json('session.code')])->assertOk()->assertJsonMissingPath('action_codes');
        $this->postJson('/api/exams/'.$s->id.'/reactivate', ['valid_hours' => 3])->assertStatus(409);
        $this->assertSame($r->json('session.code'), $s->fresh()->code);
    }

    public function test_reactivation_is_owner_only_and_validates_expiry(): void
    {
        $u = $this->teacher(); $s = $this->exam($u); $s->update(['active' => false]);
        $this->postJson('/api/exams/'.$s->id.'/reactivate', ['valid_hours' => 2])->assertUnauthorized();
        Sanctum::actingAs($this->teacher());
        $this->postJson('/api/exams/'.$s->id.'/reactivate', ['valid_hours' => 2])->assertNotFound();
        Sanctum::actingAs($u);
        foreach ([0,169,'bad'] as $hours) $this->postJson('/api/exams/'.$s->id.'/reactivate', ['valid_hours' => $hours])->assertUnprocessable();
        $this->postJson('/api/exams/'.$s->id.'/reactivate', ['valid_hours' => 1])->assertOk();
    }

    public function test_existing_sessions_receive_separate_encrypted_codes_when_migrated(): void
    {
        $u = $this->teacher(); $s = $this->exam($u);
        $migration = require database_path('migrations/2026_10_27_000001_exam_action_codes.php');
        $migration->down();
        DB::table('exam_sessions')->where('id', $s->id)->update(['require_managed_lock' => true, 'supervisor_pin_hash' => Hash::make('864209')]);
        $migration->up();
        $s = $s->fresh();
        $this->assertNotSame($s->exit_code, $s->resume_code);
        $this->assertTrue(Hash::check($s->exit_code, $s->exit_code_hash));
        $this->assertTrue(Hash::check($s->resume_code, $s->resume_code_hash));
        $this->assertFalse($s->require_managed_lock);
        $this->assertFalse($u->fresh()->require_managed_lock);
        $this->assertNotSame($s->exit_code, DB::table('exam_sessions')->where('id', $s->id)->value('exit_code'));
    }

    public function test_each_exam_has_its_own_codes(): void
    {
        $u = $this->teacher(); $one = $this->exam($u);
        $two = $one->replicate(['code']); $two->code = 'QR4P6N'; $two->save();
        $this->assertNotSame($one->exit_code, $two->exit_code);
        $this->assertNotSame($one->resume_code, $two->resume_code);
        $token = $this->join($one);
        $this->withToken($token)->postJson('/api/student/authorize', ['pin' => $two->exit_code, 'action' => 'exit'])->assertUnprocessable();
        $this->withToken($token)->postJson('/api/student/authorize', ['pin' => $two->resume_code, 'action' => 'resume'])->assertUnprocessable();
    }
}
