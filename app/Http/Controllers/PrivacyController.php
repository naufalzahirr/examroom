<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ConfirmAccountDeletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class PrivacyController extends Controller
{
    public function requestDeletion(Request $request)
    {
        $input = $request->validate(['email' => 'required|email|max:254']);
        $user = User::where('email', strtolower($input['email']))->first();

        if ($user) {
            $url = URL::temporarySignedRoute('account-deletion.confirm', now()->addHour(), ['user' => $user->id]);
            try {
                $user->notify(new ConfirmAccountDeletion($url));
            } catch (\Throwable $error) {
                report($error);
            }
        }

        return back()->with('status', 'Jika email tersebut terdaftar, kami akan mengirim tautan konfirmasi yang berlaku selama 1 jam. Belum ada data yang dihapus. Jika email tidak diterima, hubungi '.config('examroom.support_email').'.');
    }

    public function confirmDeletion(Request $request, string $user)
    {
        abort_unless(User::whereKey($user)->exists(), 404);

        return view('privacy', ['page' => 'confirm-deletion']);
    }

    public function deleteAccount(Request $request, string $user)
    {
        $request->validate(['confirmation' => 'required|in:HAPUS']);

        DB::transaction(function () use ($user) {
            $account = User::whereKey($user)->lockForUpdate()->firstOrFail();
            // Sesi ujian dan peserta mengikuti foreign key cascade pada database.
            // Token polymorphic dan rekaman sesi web tidak memiliki cascade tersebut.
            $account->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $account->email)->delete();
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $account->id)->delete();
            }
            $account->delete();
        });

        return redirect()->route('account-deletion')->with('deleted', true);
    }
}
