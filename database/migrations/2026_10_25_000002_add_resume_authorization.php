<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', fn (Blueprint $t) => $t->timestamp('resume_authorized_until')->nullable());
    }

    public function down(): void
    {
        Schema::table('participants', fn (Blueprint $t) => $t->dropColumn('resume_authorized_until'));
    }
};
