<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('school_name')->nullable();
            $t->string('google_sub')->nullable()->unique();
            $t->string('supervisor_pin_hash')->nullable();
            $t->boolean('require_managed_lock')->default(true);
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->text('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('exam_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('code', 6)->unique();
            $t->string('title', 100);
            $t->text('url');
            $t->string('platform');
            $t->string('school_name');
            $t->string('teacher_name');
            $t->unsignedInteger('duration_minutes');
            $t->timestamp('expires_at');
            $t->boolean('active')->default(true);
            $t->boolean('require_managed_lock');
            $t->string('supervisor_pin_hash');
            $t->timestamps();
        });
        Schema::create('participants', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('exam_session_id')->constrained()->cascadeOnDelete();
            $t->text('full_name');
            $t->text('participant_number');
            $t->string('token_hash', 64)->unique();
            $t->string('status')->default('joined');
            $t->string('lock_mode')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['school_name', 'google_sub', 'supervisor_pin_hash', 'require_managed_lock']));
    }
};
