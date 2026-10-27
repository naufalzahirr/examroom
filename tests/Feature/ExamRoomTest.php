<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamRoomTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(string $email = 'teacher@example.test'): User
    {
        return User::create(['name' => 'Guru Satu', 'email' => $email, 'password' => 'valid-password-123', 'school_name' => 'Sekolah Mandiri', 'supervisor_pin_hash' => Hash::make('864209'), 'require_managed_lock' => true]);
    }

    private function exam(User $u, array $extra = []): ExamSession
    {
        return ExamSession::create([...['user_id' => $u->id, 'code' => 'R7K9P2', 'title' => 'Ujian Algoritma', 'url' => 'https://docs.google.com/forms/d/e/form-one/viewform', 'platform' => 'Google Forms', 'school_name' => $u->school_name, 'teacher_name' => $u->name, 'duration_minutes' => 60, 'expires_at' => now()->addHours(2), 'require_managed_lock' => true, 'supervisor_pin_hash' => $u->supervisor_pin_hash], ...$extra]);
    }

    private function join(ExamSession $s): array
    {
        return $this->postJson('/api/student/join', ['code' => $s->code, 'full_name' => 'Peserta Uji', 'participant_number' => 'NIS-001'])->assertCreated()->json();
    }

    public function test_teacher_routes_require_authentication(): void
    {
        $this->getJson('/api/exams')->assertUnauthorized();
        $this->putJson('/api/profile', [])->assertUnauthorized();
    }

    public function test_register_login_profile_and_logout(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Guru', 'email' => 'NEW@example.test', 'password' => 'valid-password-123', 'password_confirmation' => 'valid-password-123'])->assertOk()->assertJsonPath('user.school_name', null)->assertJsonMissingPath('user.password');
        $this->postJson('/api/auth/login', ['email' => 'new@example.test', 'password' => 'wrong'])->assertUnprocessable();
        $token = $this->postJson('/api/auth/login', ['email' => 'new@example.test', 'password' => 'valid-password-123'])->assertOk()->json('token');
        $this->withToken($token)->putJson('/api/profile', ['name' => 'Guru Baru', 'school_name' => 'Sekolah Terbuka'])->assertOk()->assertJsonPath('user.school_name', 'Sekolah Terbuka');
        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_same_school_does_not_share_teacher_data(): void
    {
        $a = $this->teacher();
        $s = $this->exam($a);
        $this->join($s);
        $b = $this->teacher('other@example.test');
        Sanctum::actingAs($b);
        $this->getJson('/api/exams')->assertOk()->assertJsonCount(0, 'sessions');
        $this->getJson('/api/exams/'.$s->id)->assertNotFound();
        $this->postJson('/api/exams/'.$s->id.'/deactivate')->assertNotFound();
    }

    public function test_dashboard_and_session_counts_cover_all_pages_and_only_owned_sessions(): void
    {
        $teacher = $this->teacher();
        $exam = $this->exam($teacher);
        for ($i = 0; $i < 105; $i++) {
            Participant::create(['exam_session_id' => $exam->id, 'full_name' => 'Peserta '.$i, 'participant_number' => (string) $i, 'token_hash' => hash('sha256', 'own-'.$i), 'last_seen_at' => now()->subSeconds($i), 'status' => $i < 101 ? 'checking' : ($i < 103 ? 'in_session' : ($i === 103 ? 'paused' : 'exited'))]);
        }
        $other = $this->exam($this->teacher('other@example.test'), ['code' => 'Z7K9P2']);
        Participant::create(['exam_session_id' => $other->id, 'full_name' => 'Other', 'participant_number' => '1', 'token_hash' => hash('sha256', 'other'), 'last_seen_at' => now()->addMinute(), 'status' => 'paused']);
        Sanctum::actingAs($teacher);
        $this->getJson('/api/exams')->assertOk()->assertJsonPath('summary.participants', 105)->assertJsonPath('summary.attention', 102)->assertJsonCount(3, 'recent_activity')->assertJsonPath('recent_activity.0.name', 'Peserta 0')->assertJsonMissing(['name' => 'Other']);
        foreach ([1, 2] as $page) {
            $this->getJson('/api/exams/'.$exam->id.'?page='.$page)->assertOk()->assertJsonPath('participants_total', 105)->assertJsonPath('summary.in_session', 2)->assertJsonPath('summary.attention', 102)->assertJsonPath('summary.exited', 1);
        }
    }

    public function test_student_without_account_receives_correct_snapshot(): void
    {
        $u = $this->teacher();
        $s = $this->exam($u);
        $u->update(['name' => 'Guru Baru', 'school_name' => 'Sekolah Baru']);
        $this->postJson('/api/student/lookup', ['code' => $s->code])->assertOk()->assertJsonPath('session.title', 'Ujian Algoritma')->assertJsonPath('session.school_name', 'Sekolah Mandiri')->assertJsonPath('session.teacher_name', 'Guru Satu')->assertJsonMissingPath('session.supervisor_pin_hash')->assertJsonMissingPath('session.user_id');
        $this->join($s);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_expired_inactive_codes(): void
    {
        $s = $this->exam($this->teacher());
        $this->postJson('/api/student/lookup', ['code' => 'ZZZZZZ'])->assertNotFound();
        $s->update(['expires_at' => now()->subMinute()]);
        $this->postJson('/api/student/lookup', ['code' => $s->code])->assertGone();
        $this->postJson('/api/student/join', ['code' => $s->code, 'full_name' => 'A', 'participant_number' => '1'])->assertGone();
        $s->update(['active' => false]);
        $this->postJson('/api/student/lookup', ['code' => $s->code])->assertStatus(409);
    }

    public function test_server_generates_unique_codes_and_hashes_pin(): void
    {
        $u = $this->teacher();
        Sanctum::actingAs($u);
        for ($i = 0; $i < 4; $i++) {
            $r = $this->postJson('/api/exams', ['title' => 'Judul guru', 'url' => 'https://forms.gle/abcdef', 'duration_minutes' => 60, 'valid_hours' => 2, 'require_managed_lock' => false])->assertCreated()->assertJsonPath('session.title', 'Judul guru')->assertJsonPath('session.active', true);
            $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', $r->json('session.code'));
            $exam = ExamSession::find($r->json('session.id'));
            $this->assertTrue(Hash::check($r->json('action_codes.exit'), $exam->exit_code_hash));
            $this->assertTrue(Hash::check($r->json('action_codes.resume'), $exam->resume_code_hash));
            $this->assertNotSame($r->json('action_codes.exit'), $r->json('action_codes.resume'));
            $this->assertFalse($exam->require_managed_lock);
        } $this->assertSame(4, ExamSession::distinct()->count('code'));
    }

    public function test_unsupported_urls_and_missing_profile_rejected(): void
    {
        $u = $this->teacher();
        Sanctum::actingAs($u);
        $d = ['title' => 'Ujian', 'url' => 'https://evil.test/forms', 'duration_minutes' => 60, 'valid_hours' => 2, 'require_managed_lock' => true];
        $this->postJson('/api/exams', $d)->assertUnprocessable();
        $this->postJson('/api/exams', [...$d, 'url' => 'http://forms.gle/test'])->assertUnprocessable();
        $u->update(['school_name' => null]);
        $this->postJson('/api/exams', [...$d, 'url' => 'https://forms.gle/test'])->assertUnprocessable();
    }

    public function test_managed_device_is_not_required_but_screen_pinning_is(): void
    {
        $j = $this->join($this->exam($this->teacher()));
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'none'])->assertUnprocessable();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'pinned'])->assertOk();
    }

    public function test_pinning_allowed_only_by_teacher_policy(): void
    {
        $j = $this->join($this->exam($this->teacher(), ['require_managed_lock' => false]));
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'pinned'])->assertOk();
    }

    public function test_exit_requires_pin_and_is_idempotent(): void
    {
        $j = $this->join($this->exam($this->teacher()));
        $url = '/api/student/authorize';
        $this->withToken($j['participant_token'])->postJson($url, ['pin' => '000000', 'action' => 'exit'])->assertUnprocessable();
        $this->assertNull(Participant::first()->ended_at);
        $this->withToken($j['participant_token'])->postJson($url, ['pin' => ExamSession::first()->exit_code, 'action' => 'exit'])->assertOk()->assertJsonPath('participant.status', 'exited');
        $this->withToken($j['participant_token'])->postJson($url, ['pin' => ExamSession::first()->exit_code, 'action' => 'exit'])->assertOk();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertStatus(409);
    }

    public function test_pin_limit_shared_across_session_participants(): void
    {
        $s = $this->exam($this->teacher());
        $one = $this->join($s);
        $two = $this->join($s);
        for ($i = 0; $i < 5; $i++) {
            $this->withToken($one['participant_token'])->postJson('/api/student/authorize', ['pin' => '000000', 'action' => 'exit'])->assertUnprocessable();
        } $this->withToken($two['participant_token'])->postJson('/api/student/authorize', ['pin' => ExamSession::first()->exit_code, 'action' => 'exit'])->assertStatus(429);
    }

    public function test_participant_identity_encrypted_token_hashed(): void
    {
        $j = $this->join($this->exam($this->teacher()));
        $row = DB::table('participants')->first();
        $this->assertNotSame('Peserta Uji', $row->full_name);
        $this->assertNotSame('NIS-001', $row->participant_number);
        $this->assertSame(hash('sha256', $j['participant_token']), $row->token_hash);
        $this->assertArrayNotHasKey('token_hash', $j['participant']);
    }

    public function test_participant_cannot_access_teacher_routes(): void
    {
        $j = $this->join($this->exam($this->teacher()));
        $this->withToken($j['participant_token'])->getJson('/api/exams')->assertUnauthorized();
        $this->withToken(str_repeat('a', 64))->postJson('/api/student/authorize', ['pin' => ExamSession::first()->exit_code, 'action' => 'exit'])->assertNotFound();
    }

    public function test_legacy_security_settings_cannot_change_exam_codes_or_require_managed_devices(): void
    {
        $u = $this->teacher();
        $s = $this->exam($u);
        $codes = $s->teacherCodes();
        Sanctum::actingAs($u);
        $this->putJson('/api/security', ['pin' => '111111', 'require_managed_lock' => true])->assertOk()->assertJsonMissingPath('user.supervisor_pin_hash')->assertJsonPath('user.require_managed_lock', false);
        $this->assertSame($codes, $s->fresh()->teacherCodes());
    }

    public function test_duration_expiry_blocks_resume(): void
    {
        $j = $this->join($this->exam($this->teacher()));
        Participant::first()->update(['started_at' => now()->subMinutes(61)]);
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertStatus(409);
    }

    public function test_restart_recovery_counts_powered_off_time_and_requires_pin(): void
    {
        $this->travelTo(now()->startOfSecond());
        $j = $this->join($this->exam($this->teacher()));
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])
            ->assertOk()->assertJsonPath('timing.remaining_ms', 3600000);
        $started = Participant::first()->started_at->toISOString();
        $this->travel(40)->minutes();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'paused', 'lock_mode' => 'none'])
            ->assertOk()->assertJsonPath('timing.remaining_ms', 1200000);
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertForbidden();
        $this->withToken($j['participant_token'])->postJson('/api/student/authorize', ['pin' => '000000', 'action' => 'resume'])->assertUnprocessable();
        $this->travel(5)->minutes();
        $this->withToken($j['participant_token'])->postJson('/api/student/authorize', ['pin' => ExamSession::first()->resume_code, 'action' => 'resume'])
            ->assertOk()->assertJsonPath('timing.remaining_ms', 900000);
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])
            ->assertOk()->assertJsonPath('timing.remaining_ms', 900000);
        $this->assertSame($started, Participant::first()->started_at->toISOString());
    }

    public function test_recovery_at_deadline_reports_zero_and_cannot_resume(): void
    {
        $this->travelTo(now()->startOfSecond());
        $j = $this->join($this->exam($this->teacher()));
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertOk();
        $this->travel(60)->minutes();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'paused', 'lock_mode' => 'none'])
            ->assertOk()->assertJsonPath('timing.remaining_ms', 0);
        $this->withToken($j['participant_token'])->postJson('/api/student/authorize', ['pin' => ExamSession::first()->resume_code, 'action' => 'resume'])->assertStatus(409);
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertForbidden();
        $this->withToken($j['participant_token'])->postJson('/api/student/authorize', ['pin' => ExamSession::first()->exit_code, 'action' => 'exit'])->assertOk();
    }

    public function test_google_unconfigured_is_explicit_error(): void
    {
        config(['examroom.google_client_id' => null]);
        $this->postJson('/api/auth/google', ['id_token' => 'fake', 'nonce' => str_repeat('a', 64)])->assertStatus(503);
    }

    public function test_reset_password_revokes_tokens(): void
    {
        Notification::fake();
        $u = $this->teacher();
        $u->createToken('old');
        $this->postJson('/api/auth/forgot', ['email' => $u->email])->assertOk();
        Notification::assertSentTo($u, ResetPassword::class);
        $token = Password::createToken($u);
        $this->postJson('/api/auth/reset', ['email' => $u->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(Hash::check('new-password-123', $u->fresh()->password));
    }

    public function test_code_and_login_rate_limits(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->postJson('/api/student/lookup', ['code' => 'ZZZZZZ'])->assertNotFound();
        } $this->postJson('/api/student/lookup', ['code' => 'ZZZZZZ'])->assertStatus(429);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'bad'])->assertUnprocessable();
        } $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'bad'])->assertStatus(429);
    }

    public function test_web_and_reset_page_render(): void
    {
        $this->get('/teacher')->assertOk()->assertSee('ExamRoom');
        $this->get('/reset-password/test-token?email=a@example.test')->assertOk();
    }

    public function test_paused_session_requires_server_supervisor_authorization(): void
    {
        $j = $this->join($this->exam($this->teacher()));
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertOk();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'paused', 'lock_mode' => 'managed'])->assertOk();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'checking', 'lock_mode' => 'managed'])->assertForbidden();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertForbidden();
        $this->withToken($j['participant_token'])->postJson('/api/student/authorize', ['pin' => ExamSession::first()->resume_code, 'action' => 'resume'])->assertOk();
        $this->withToken($j['participant_token'])->postJson('/api/student/status', ['status' => 'in_session', 'lock_mode' => 'managed'])->assertOk();
    }

    public function test_database_cache_pin_failures_survive_failed_authorization(): void
    {
        config(['cache.default' => 'database']);
        app('cache')->forgetDriver('database');
        app()->instance(RateLimiter::class, new RateLimiter(app('cache')->store('database')));
        \Illuminate\Support\Facades\RateLimiter::clearResolvedInstance(RateLimiter::class);
        (new AppServiceProvider(app()))->boot();
        $j = $this->join($this->exam($this->teacher()));
        for ($i = 0; $i < 5; $i++) {
            $this->withToken($j['participant_token'])->postJson('/api/student/authorize', ['pin' => '000000', 'action' => 'exit'])->assertUnprocessable();
        }
        $this->withToken($j['participant_token'])->postJson('/api/student/authorize', ['pin' => ExamSession::first()->exit_code, 'action' => 'exit'])->assertStatus(429);
    }

    public function test_successful_code_lookups_do_not_block_school_nat(): void
    {
        $s = $this->exam($this->teacher());
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/student/lookup', ['code' => $s->code])->assertOk();
        }
    }
}
