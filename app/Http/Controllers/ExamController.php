<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    public function index(Request $r)
    {
        $q = ExamSession::where('user_id', $r->user()->id);
        $page = (clone $q)->withCount('participants')->latest()->paginate(50);
        $activity = Participant::whereIn('exam_session_id', (clone $q)->select('id'))
            ->whereNotNull('last_seen_at')->latest('last_seen_at')->limit(3)->get()
            ->map(fn (Participant $p) => ['name' => $p->full_name, 'status' => $p->status, 'at' => $p->last_seen_at]);

        return ['recent_activity' => $activity, 'sessions' => $page->items(), 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'summary' => ['sessions' => (clone $q)->count(), 'active' => (clone $q)->where('active', true)->where('expires_at', '>', now())->count(), 'participants' => Participant::whereIn('exam_session_id', (clone $q)->select('id'))->count(), 'attention' => Participant::whereIn('exam_session_id', (clone $q)->select('id'))->whereIn('status', ['checking', 'paused'])->count()]];
    }

    private function owned(Request $r, string $id): ExamSession
    {
        return ExamSession::where('user_id', $r->user()->id)->findOrFail($id);
    }

    public function show(Request $r, string $id)
    {
        $s = $this->owned($r, $id);
        $page = $s->participants()->latest()->paginate(100);

        return ['action_codes' => $s->teacherCodes(), 'session' => $s, 'participants' => $page->items(), 'participants_total' => $page->total(), 'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'summary' => ['in_session' => $s->participants()->where('status', 'in_session')->count(), 'attention' => $s->participants()->whereIn('status', ['checking', 'paused'])->count(), 'exited' => $s->participants()->where('status', 'exited')->count()]];
    }

    public function deactivate(Request $r, string $id)
    {
        $s = $this->owned($r, $id);
        $s->update(['active' => false]);

        return ['session' => $s];
    }

    public function create(Request $r)
    {
        $u = $r->user();
        abort_unless($u->school_name, 422, 'Lengkapi profil sekolah terlebih dahulu.');
        $v = $r->validate(['title' => 'required|string|max:100', 'url' => 'required|url:https|max:2048', 'duration_minutes' => 'required|integer|min:1|max:480', 'valid_hours' => 'required|integer|min:1|max:168']);
        $url = parse_url($v['url']);
        $host = strtolower($url['host'] ?? '');
        abort_if(isset($url['user']) || isset($url['pass']) || (isset($url['port']) && $url['port'] !== 443), 422, 'URL ujian tidak didukung.');
        // Public launch supports Google Forms only; expand with tested provider policies.
        abort_unless($host === 'forms.gle' || ($host === 'docs.google.com' && str_starts_with($url['path'] ?? '', '/forms/')), 422, 'Gunakan URL Google Forms (docs.google.com/forms atau forms.gle).');
        $code = $this->newEntryCode();
        unset($v['valid_hours']);
        $s = ExamSession::create([...$v, 'user_id' => $u->id, 'code' => $code, 'platform' => 'Google Forms', 'school_name' => $u->school_name, 'teacher_name' => $u->name, 'expires_at' => now()->addHours($r->integer('valid_hours'))]);

        return response()->json(['session' => $s->refresh(), 'action_codes' => $s->teacherCodes()], 201);
    }

    private function newEntryCode(?string $previous = null): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)];
            }
        } while ($code === $previous || ExamSession::where('code', $code)->exists());
        return $code;
    }

    public function reactivate(Request $r, string $id)
    {
        $v = $r->validate(['valid_hours' => 'required|integer|min:1|max:168']);
        return DB::transaction(function () use ($r, $id, $v) {
            $s = ExamSession::where('user_id', $r->user()->id)->lockForUpdate()->findOrFail($id);
            abort_if($s->active && $s->expires_at->isFuture(), 409, 'Ujian masih aktif. Kode tidak diubah.');
            $s->update(['code' => $this->newEntryCode($s->code), 'active' => true, 'expires_at' => now()->addHours($v['valid_hours']), 'require_managed_lock' => false]);
            return ['session' => $s->refresh(), 'action_codes' => $s->teacherCodes()];
        });
    }

    public function resetCode(Request $r, string $id)
    {
        $v = $r->validate(['kind' => 'required|in:entry,exit,resume']);

        return DB::transaction(function () use ($r, $id, $v) {
            $s = ExamSession::where('user_id', $r->user()->id)->lockForUpdate()->findOrFail($id);
            if ($v['kind'] === 'entry') {
                $s->update(['code' => $this->newEntryCode($s->code)]);
            } else {
                do {
                    $code = (string) random_int(100000, 999999);
                } while (in_array($code, [$s->exit_code, $s->resume_code], true));
                $s->update([$v['kind'].'_code' => $code, $v['kind'].'_code_hash' => Hash::make($code)]);
            }

            return ['session' => $s->refresh(), 'action_codes' => $s->teacherCodes()];
        });
    }

    private function resolveCode(Request $r, string $code, bool $lock = false): ExamSession
    {
        $key = 'invalid-code:'.$r->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 12), 429, 'Terlalu banyak kode salah. Tunggu sebentar.');
        $query = ExamSession::where('code', $code);
        if ($lock) {
            $query->lockForUpdate();
        }
        $s = $query->first();
        if (! $s) {
            RateLimiter::hit($key, 60);
            abort(404, 'Kode tidak ditemukan.');
        }

        return $s;
    }

    public function lookup(Request $r)
    {
        $v = $r->validate(['code' => 'required|string|regex:/^[A-Z2-9]{6}$/']);
        $s = $this->resolveCode($r, $v['code']);
        $s->assertAvailable();

        return ['session' => $s];
    }

    public function join(Request $r)
    {
        $v = $r->validate(['code' => 'required|string|size:6', 'full_name' => 'required|string|max:100', 'participant_number' => 'required|string|max:50']);
        $resolved = $this->resolveCode($r, $v['code']);

        return DB::transaction(function () use ($v, $resolved) {
            $s = ExamSession::whereKey($resolved->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($s->code, $v['code']), 404, 'Kode tidak ditemukan.');
            $s->assertAvailable();
            $token = bin2hex(random_bytes(32));
            $p = Participant::create(['exam_session_id' => $s->id, 'full_name' => $v['full_name'], 'participant_number' => $v['participant_number'], 'token_hash' => hash('sha256', $token), 'status' => 'checking', 'last_seen_at' => now()]);

            return response()->json(['participant' => $p, 'participant_token' => $token, 'session' => $s], 201);
        });
    }

    private function participant(Request $r): Participant
    {
        $token = $r->bearerToken();
        abort_unless($token && strlen($token) === 64, 401);

        return Participant::where('token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
    }

    public function status(Request $r)
    {
        return DB::transaction(function () use ($r) {
            $v = $r->validate(['status' => 'required|in:checking,in_session,paused', 'lock_mode' => 'nullable|in:managed,pinned,none']);
            $p = $this->participant($r);
            abort_if($p->ended_at, 409, 'Sesi peserta telah diakhiri.');
            abort_if($v['status'] === 'checking' && ! in_array($p->status, ['joined', 'checking']), 403, 'Status pemeriksaan tidak dapat diulang setelah sesi berjalan.');
            $s = $p->examSession;
            if ($v['status'] === 'in_session') {
                $s->assertAvailable();
                abort_if($p->status === 'paused' && (! $p->resume_authorized_until || $p->resume_authorized_until->isPast()), 403, 'Kode lanjut ujian diperlukan untuk melanjutkan sesi.');
                abort_unless(in_array($v['lock_mode'] ?? '', ['managed', 'pinned']), 422, 'Mode penguncian yang diwajibkan belum aktif.');
                abort_if($p->started_at && $p->started_at->copy()->addMinutes($s->duration_minutes)->lessThanOrEqualTo(now()), 409, 'Waktu ujian habis. Minta pengawas mengakhiri sesi.');
            }
            $p->fill([...$v, 'last_seen_at' => now()]);
            $p->resume_authorized_until = null;
            if ($v['status'] === 'in_session' && ! $p->started_at) {
                $p->started_at = now();
            }
            $p->save();

            return ['participant' => $p, 'timing' => $this->timing($p)];
        });
    }

    private function timing(Participant $p): array
    {
        $now = now();
        $deadline = ($p->started_at?->copy() ?? $now->copy())->addMinutes($p->examSession->duration_minutes);

        return [
            'server_now_ms' => $now->getTimestampMs(),
            'deadline_at_ms' => $deadline->getTimestampMs(),
            'remaining_ms' => max(0, $deadline->getTimestampMs() - $now->getTimestampMs()),
        ];
    }

    public function authorize(Request $r)
    {
        $v = $r->validate(['pin' => 'required|digits:6', 'action' => 'required|in:exit,resume']);
        $p = $this->participant($r);
        $key = 'pin:'.$p->exam_session_id.':'.$v['action'];
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Terlalu banyak percobaan kode. Tunggu 60 detik.');
        $result = DB::transaction(function () use ($r, $v, $key) {
            $p = $this->participant($r);
            $s = ExamSession::whereKey($p->exam_session_id)->lockForUpdate()->firstOrFail();
            $p->setRelation('examSession', $s);
            $hash = $v['action'] === 'exit' ? $s->exit_code_hash : $s->resume_code_hash;
            if (! $hash || ! Hash::check($v['pin'], $hash)) {
                RateLimiter::hit($key, 60);
                return ValidationException::withMessages(['pin' => $v['action'] === 'exit' ? 'Kode keluar ujian salah.' : 'Kode lanjut ujian salah.']);
            }
            RateLimiter::clear($key);

            if ($v['action'] === 'exit') {
                $p->update(['status' => 'exited', 'ended_at' => $p->ended_at ?? now(), 'last_seen_at' => now()]);
            } else {
                $p->examSession->assertAvailable();
                abort_if($p->started_at && $p->started_at->copy()->addMinutes($p->examSession->duration_minutes)->lessThanOrEqualTo(now()), 409, 'Waktu ujian habis.');
                $p->update(['status' => 'paused', 'ended_at' => null, 'resume_authorized_until' => now()->addMinute(), 'last_seen_at' => now()]);
            }

            return ['authorized' => true, 'participant' => $p, 'timing' => $this->timing($p)];
        });
        // Commit failed-attempt counters even when using the database cache store.
        if ($result instanceof ValidationException) {
            throw $result;
        }

        return $result;
    }
}
