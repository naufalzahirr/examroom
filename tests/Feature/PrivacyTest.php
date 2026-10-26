<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\ConfirmAccountDeletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_are_public_and_teacher_links_are_visible(): void
    {
        foreach (['/privacy', '/delete-account', '/support'] as $path) {
            $this->get($path)->assertOk()->assertSee('support@examroom.smkn4tpi.sch.id');
        }
        $this->get('/teacher')->assertOk()->assertSee('href="/privacy"', false)->assertSee('href="/delete-account"', false);
        $this->get('/privacy')->assertSee('30 hari')->assertSee('Google Forms');
    }

    public function test_request_validates_email_hides_account_existence_and_does_not_delete(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'teacher@example.test']);
        $this->post('/delete-account', ['email' => 'invalid'])->assertSessionHasErrors('email');
        $this->from('/delete-account')->post('/delete-account', ['email' => 'TEACHER@example.test'])->assertRedirect('/delete-account')->assertSessionHas('status');
        $message = session('status');
        $this->post('/delete-account', ['email' => 'missing@example.test'])->assertSessionHas('status', $message);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        Notification::assertSentTo($user, ConfirmAccountDeletion::class, function ($notification) {
            $this->get($notification->url)->assertOk()->assertSee('KONFIRMASI TERAKHIR');
            return true;
        });
        Notification::assertCount(1);
    }

    public function test_confirmation_deletes_owned_data_and_tokens_but_preserves_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $exam = ExamSession::create(['user_id' => $user->id, 'code' => 'R7K9P2', 'title' => 'Demo', 'url' => 'https://docs.google.com/forms/d/e/demo/viewform', 'platform' => 'Google Forms', 'school_name' => 'Demo', 'teacher_name' => $user->name, 'duration_minutes' => 60, 'expires_at' => now()->addHour(), 'active' => true, 'require_managed_lock' => false, 'supervisor_pin_hash' => 'hash']);
        Participant::create(['exam_session_id' => $exam->id, 'full_name' => 'Dummy', 'participant_number' => '001', 'token_hash' => hash('sha256', 'participant'), 'status' => 'checking']);
        $user->createToken('test'); $other->createToken('other');
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'test', 'created_at' => now()]);
        $url = URL::temporarySignedRoute('account-deletion.confirm', now()->addHour(), ['user' => $user->id]);
        $this->get($url)->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->post($url, ['confirmation' => 'no'])->assertSessionHasErrors('confirmation');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->post($url, ['confirmation' => 'HAPUS'])->assertRedirect('/delete-account')->assertSessionHas('deleted', true);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('exam_sessions', ['id' => $exam->id]);
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->post($url, ['confirmation' => 'HAPUS'])->assertNotFound();
    }

    public function test_unsigned_expired_or_tampered_links_cannot_delete(): void
    {
        $user = User::factory()->create(); $other = User::factory()->create();
        $expired = URL::temporarySignedRoute('account-deletion.confirm', now()->subMinute(), ['user' => $user->id]);
        $this->get($expired)->assertForbidden();
        $this->post($expired, ['confirmation' => 'HAPUS'])->assertForbidden();
        $this->post('/delete-account/confirm/'.$user->id, ['confirmation' => 'HAPUS'])->assertForbidden();
        $valid = URL::temporarySignedRoute('account-deletion.confirm', now()->addHour(), ['user' => $user->id]);
        $tampered = str_replace('/confirm/'.$user->id.'?', '/confirm/'.$other->id.'?', $valid);
        $this->post($tampered, ['confirmation' => 'HAPUS'])->assertForbidden();
        $this->assertDatabaseCount('users', 2);
    }

    public function test_requests_are_rate_limited_per_email(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'teacher@example.test']);
        for ($i = 0; $i < 3; $i++) $this->post('/delete-account', ['email' => 'teacher@example.test'])->assertRedirect();
        $this->post('/delete-account', ['email' => 'teacher@example.test'])->assertTooManyRequests();
        Notification::assertCount(3);
    }
}
