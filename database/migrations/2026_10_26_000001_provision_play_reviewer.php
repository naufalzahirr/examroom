<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Provisioning is now explicitly opt-in: db:seed --class=PlayReviewerSeeder.
        // Keep this migration name for installations that already recorded it.
    }

    public function down(): void
    {
        // Never delete an existing review account during migration rollback.
    }
};
