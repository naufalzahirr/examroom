<?php

use App\Models\Participant;
use Illuminate\Support\Facades\Schedule;
use Laravel\Sanctum\PersonalAccessToken;

Schedule::call(function () {
    Participant::where('updated_at', '<', now()->subDays(config('examroom.participant_retention_days')))->delete();
    PersonalAccessToken::where('expires_at', '<', now())->delete();
})->daily();
