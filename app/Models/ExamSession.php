<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class ExamSession extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['supervisor_pin_hash', 'user_id', 'exit_code', 'resume_code', 'exit_code_hash', 'resume_code_hash'];

    protected function casts(): array
    {
        return ['exit_code' => 'encrypted', 'resume_code' => 'encrypted', 'expires_at' => 'datetime', 'active' => 'boolean', 'require_managed_lock' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (ExamSession $session) {
            $exit = (string) random_int(100000, 999999);
            do { $resume = (string) random_int(100000, 999999); } while ($resume === $exit);
            $session->exit_code = $exit;
            $session->resume_code = $resume;
            $session->exit_code_hash = Hash::make($exit);
            $session->resume_code_hash = Hash::make($resume);
            // Legacy columns remain for installed clients; every session permits screen pinning.
            $session->require_managed_lock = false;
            $session->supervisor_pin_hash = Hash::make(bin2hex(random_bytes(32)));
        });
    }

    public function teacherCodes(): array
    {
        return ['exit' => $this->exit_code, 'resume' => $this->resume_code];
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function assertAvailable(): void
    {
        abort_unless($this->active, 409, 'Sesi ujian tidak aktif.');
        abort_if($this->expires_at->isPast(), 410, 'Kode ujian sudah kedaluwarsa.');
    }
}
