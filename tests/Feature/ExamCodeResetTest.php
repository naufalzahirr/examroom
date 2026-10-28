<?php
namespace Tests\Feature;

use App\Models\{ExamSession, Participant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamCodeResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_codes_reset_independently_while_active_and_preserve_participant_clock(): void
    {
        $u = User::factory()->create();
        $s = ExamSession::create(['user_id'=>$u->id,'code'=>'AB7K9M','title'=>'Ujian','school_name'=>'Sekolah','teacher_name'=>'Guru','platform'=>'Google Forms','url'=>'https://forms.gle/qa','duration_minutes'=>60,'expires_at'=>now()->addHours(2)]);
        $token = $this->postJson('/api/student/join', ['code'=>$s->code,'full_name'=>'Peserta','participant_number'=>'001'])->assertCreated()->json('participant_token');
        $this->withToken($token)->postJson('/api/student/status', ['status'=>'in_session','lock_mode'=>'pinned'])->assertOk();
        foreach (['entry','exit','resume'] as $kind) {
            $before = $s->fresh();
            $participant = Participant::first()->getAttributes();
            Sanctum::actingAs($u);
            $r = $this->postJson('/api/exams/'.$s->id.'/reset-code', ['kind'=>$kind])->assertOk();
            $after = $s->fresh();
            $this->assertSame($before->expires_at->toISOString(), $after->expires_at->toISOString());
            $this->assertSame($before->active, $after->active);
            $this->assertSame($before->duration_minutes, $after->duration_minutes);
            $this->assertSame($participant, Participant::first()->getAttributes());
            foreach (['entry'=>'code','exit'=>'exit_code','resume'=>'resume_code'] as $name=>$field) {
                if ($name === $kind) $this->assertNotSame($before->$field, $after->$field);
                else $this->assertSame($before->$field, $after->$field);
            }
            $this->assertNotSame($after->exit_code, $after->resume_code);
            if ($kind === 'entry') {
                $this->postJson('/api/student/lookup', ['code'=>$before->code])->assertNotFound();
                $this->postJson('/api/student/lookup', ['code'=>$after->code])->assertOk()->assertJsonMissingPath('action_codes');
                $this->postJson('/api/student/join', ['code'=>$before->code,'full_name'=>'Baru','participant_number'=>'002'])->assertNotFound();
            } else {
                $field = $kind.'_code';
                $this->assertTrue(Hash::check($after->$field, $after->getAttribute($kind.'_code_hash')));
                $this->withToken($token)->postJson('/api/student/authorize', ['pin'=>$before->$field,'action'=>$kind])->assertUnprocessable();
                $this->withToken($token)->postJson('/api/student/authorize', ['pin'=>$after->$field,'action'=>$kind])->assertOk();
                $this->assertSame($participant['started_at'], Participant::first()->getAttributes()['started_at']);
            }
        }
    }

    public function test_reset_is_owner_only_and_does_not_reactivate_or_extend_exam(): void
    {
        $u = User::factory()->create();
        $s = ExamSession::create(['user_id'=>$u->id,'code'=>'AB7K9M','title'=>'Ujian','school_name'=>'Sekolah','teacher_name'=>'Guru','platform'=>'Google Forms','url'=>'https://forms.gle/qa','duration_minutes'=>60,'expires_at'=>now()->subMinute()]);
        $path = '/api/exams/'.$s->id.'/reset-code';
        $this->postJson($path, ['kind'=>'entry'])->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create());
        $this->postJson($path, ['kind'=>'entry'])->assertNotFound();
        Sanctum::actingAs($u);
        foreach ([[],['kind'=>'all'],['kind'=>'']] as $data) $this->postJson($path,$data)->assertUnprocessable();
        $expiry = $s->expires_at->toISOString();
        foreach ([true,false] as $active) {
            $s->update(['active'=>$active]);
            foreach (['entry','exit','resume'] as $kind) {
                $this->postJson($path,['kind'=>$kind])->assertOk()->assertJsonPath('session.active',$active);
                $this->assertSame($expiry,$s->fresh()->expires_at->toISOString());
                $this->postJson('/api/student/lookup',['code'=>$s->fresh()->code])->assertStatus($active?410:409);
            }
        }
    }
}
