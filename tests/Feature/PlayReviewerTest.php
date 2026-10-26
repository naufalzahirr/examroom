<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlayReviewerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayReviewerTest extends TestCase
{
    use RefreshDatabase;

    private function provision(): void
    {
        $this->seed(PlayReviewerSeeder::class);
    }

    public function test_provisions_a_normal_teacher_and_a_long_lived_pinning_demo_without_participants(): void
    {
        $this->provision();
        $user = User::where('email', 'play-reviewer@examroom.smkn4tpi.sch.id')->firstOrFail();
        $this->assertSame('bcrypt', password_get_info($user->getRawOriginal('password'))['algoName']);
        $this->assertSame('bcrypt', password_get_info($user->getRawOriginal('supervisor_pin_hash'))['algoName']);
        $this->assertFalse($user->require_managed_lock);
        $exam = DB::table('exam_sessions')->where('user_id', $user->id)->first();
        $this->assertNotNull($exam);
        $this->assertSame(120, $exam->duration_minutes);
        $this->assertFalse((bool) $exam->require_managed_lock);
        $this->assertGreaterThan(now()->addYears(4)->timestamp, strtotime($exam->expires_at));
        $this->assertDatabaseCount('participants', 0);
        $this->postJson('/api/student/lookup', ['code' => $exam->code])->assertOk()->assertJsonPath('session.title', 'Google Play Review — Demo Exam')->assertJsonMissingPath('session.supervisor_pin_hash');
        $token = $this->postJson('/api/student/join', ['code' => $exam->code, 'full_name' => 'Demo Reviewer', 'participant_number' => 'DEMO-001'])->assertCreated()->json('participant_token');
        $this->withToken($token)->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'pinned'])->assertOk();
    }

    public function test_repeated_runs_preserve_changed_password_and_session_state(): void
    {
        $this->provision();
        $user = User::where('email', 'play-reviewer@examroom.smkn4tpi.sch.id')->firstOrFail();
        $user->update(['password' => 'changed-by-owner-123']);
        $password = $user->getRawOriginal('password');
        DB::table('exam_sessions')->where('user_id', $user->id)->update(['active' => false]);
        $this->provision();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('exam_sessions', 1);
        $this->assertSame($password, $user->fresh()->getRawOriginal('password'));
        $this->assertFalse((bool) DB::table('exam_sessions')->where('user_id', $user->id)->value('active'));
    }

    public function test_reviewer_cannot_access_other_teacher_sessions(): void
    {
        $this->provision();
        $reviewer = User::where('email', 'play-reviewer@examroom.smkn4tpi.sch.id')->firstOrFail();
        $other = User::factory()->create();
        $exam = (array) DB::table('exam_sessions')->where('user_id', $reviewer->id)->first();
        DB::table('exam_sessions')->where('id', $exam['id'])->update(['user_id' => $other->id]);
        Sanctum::actingAs($reviewer);
        $this->getJson('/api/exams')->assertOk()->assertJsonCount(0, 'sessions');
        $this->getJson('/api/exams/'.$exam['id'])->assertNotFound();
        $this->postJson('/api/exams/'.$exam['id'].'/deactivate')->assertNotFound();
    }

    public function test_code_collision_rolls_back_without_touching_existing_teacher(): void
    {
        $this->provision();
        $reviewer = User::where('email', 'play-reviewer@examroom.smkn4tpi.sch.id')->firstOrFail();
        $exam = (array) DB::table('exam_sessions')->where('user_id', $reviewer->id)->first();
        $reviewer->delete();
        $other = User::factory()->create();
        $exam['user_id'] = $other->id;
        DB::table('exam_sessions')->insert($exam);
        try {
            $this->provision();
            $this->fail('A code collision must stop provisioning.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('already in use', $e->getMessage());
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
        $this->assertDatabaseHas('exam_sessions', ['id' => $exam['id'], 'user_id' => $other->id]);
    }

    public function test_migration_does_not_create_accounts_and_rollback_keeps_seeded_accounts(): void
    {
        $migration = require database_path('migrations/2026_10_26_000001_provision_play_reviewer.php');
        $migration->up();
        $this->assertDatabaseCount('users', 0);
        $this->provision();
        $migration->down();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('exam_sessions', 1);
    }
}
