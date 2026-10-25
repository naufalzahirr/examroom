<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $hidden = ['token_hash', 'resume_authorized_until'];

    protected function casts(): array
    {
        return ['full_name' => 'encrypted', 'participant_number' => 'encrypted', 'resume_authorized_until' => 'datetime', 'started_at' => 'datetime', 'last_seen_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }
}
