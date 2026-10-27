<?php

namespace App\Http\Controllers;

use App\Models\User;
use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function response(User $u)
    {
        return response()->json(['user' => $u, 'token' => $u->createToken('examroom', ['*'], now()->addDays(7))->plainTextToken]);
    }

    public function register(Request $r)
    {
        $r->merge(['email' => strtolower($r->input('email', ''))]);
        $v = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:254|unique:users', 'password' => 'required|string|min:12|max:128|confirmed']);
        $v['email'] = strtolower($v['email']);

        return $this->response(User::create($v));
    }

    public function login(Request $r)
    {
        $v = $r->validate(['email' => 'required|email', 'password' => 'required|string|max:128']);
        $u = User::where('email', strtolower($v['email']))->first();
        if (! $u || ! Hash::check($v['password'], $u->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }

return $this->response($u);
    }

    public function nonce()
    {
        $n = Str::random(64);
        cache()->put('google-nonce:'.hash('sha256', $n), true, now()->addMinutes(5));

        return ['nonce' => $n];
    }

    public function google(Request $r)
    {
        abort_unless(config('examroom.google_client_id'), 503, 'Login Google belum dikonfigurasi.');
        $v = $r->validate(['id_token' => 'required|string|max:10000', 'nonce' => 'required|string|size:64']);
        $client = new Client(['client_id' => config('examroom.google_client_id')]);
        try {
            $p = $client->verifyIdToken($v['id_token']);
        } catch (\Throwable $e) {
            $p = false;
        }
        abort_unless($p && ($p['email_verified'] ?? false) && in_array($p['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com']) && hash_equals($v['nonce'], $p['nonce'] ?? ''), 401, 'Login Google gagal. Silakan ulangi.');
        abort_unless(cache()->pull('google-nonce:'.hash('sha256', $v['nonce'])) && cache()->add('google-used:'.hash('sha256', $v['nonce']), true, now()->addMinutes(5)), 401, 'Permintaan login kedaluwarsa.');
        $u = User::where('google_sub', $p['sub'])->first();
        if (! $u) {
            abort_if(User::where('email', strtolower($p['email']))->exists(), 409, 'Email sudah terdaftar. Masuk menggunakan kata sandi untuk akun tersebut.');
            $u = User::create(['email' => strtolower($p['email']), 'name' => $p['name'] ?? 'Guru', 'google_sub' => $p['sub'], 'password' => Str::random(64), 'email_verified_at' => now()]);
        }

        return $this->response($u);
    }

    public function forgot(Request $r)
    {
        $v = $r->validate(['email' => 'required|email']);
        Password::sendResetLink(['email' => strtolower($v['email'])]);

        return ['message' => 'Jika email terdaftar, tautan pemulihan akan dikirim.'];
    }

    public function reset(Request $r)
    {
        $v = $r->validate(['email' => 'required|email', 'token' => 'required|string', 'password' => 'required|string|min:12|max:128|confirmed']);
        $status = Password::reset($v, function (User $u, string $password) {
            DB::transaction(function () use ($u, $password) {
                $u->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                $u->tokens()->delete();
            });
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Tautan tidak berlaku atau kedaluwarsa.']);
        }

return ['message' => 'Kata sandi berhasil diganti. Silakan masuk.'];
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function profile(Request $r)
    {
        $v = $r->validate(['name' => 'required|string|max:100', 'school_name' => 'required|string|max:150']);
        $r->user()->update($v);

        return ['user' => $r->user()];
    }

    public function security(Request $r)
    {
        // Kept as an authenticated compatibility endpoint for older installed clients.
        $r->user()->update(['require_managed_lock' => false]);
        return ['user' => $r->user()];
    }
}
