<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['supervisor_pin_hash', 'user_id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'active' => 'boolean', 'require_managed_lock' => 'boolean'];
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
