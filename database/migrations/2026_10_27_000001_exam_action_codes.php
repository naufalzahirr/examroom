<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->text('exit_code')->nullable();
            $table->text('resume_code')->nullable();
            $table->string('exit_code_hash')->nullable();
            $table->string('resume_code_hash')->nullable();
        });
        DB::table('exam_sessions')->orderBy('id')->chunkById(100, function ($sessions) {
            foreach ($sessions as $session) {
                $exit = (string) random_int(100000, 999999);
                do { $resume = (string) random_int(100000, 999999); } while ($resume === $exit);
                DB::table('exam_sessions')->where('id', $session->id)->update([
                    'exit_code' => Crypt::encryptString($exit),
                    'resume_code' => Crypt::encryptString($resume),
                    'exit_code_hash' => Hash::make($exit),
                    'resume_code_hash' => Hash::make($resume),
                    'require_managed_lock' => false,
                ]);
            }
        });
        DB::table('users')->update(['require_managed_lock' => false]);
    }

    public function down(): void
    {
        Schema::table('exam_sessions', fn (Blueprint $table) => $table->dropColumn(['exit_code', 'resume_code', 'exit_code_hash', 'resume_code_hash']));
    }
};
