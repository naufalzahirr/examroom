<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! config('examroom.provision_reviewer', true)) {
            return;
        }

        DB::transaction(function () {
            // Never reset credentials or alter an existing account on a repeated run.
            if (DB::table('users')->where('email', 'play-reviewer@examroom.smkn4tpi.sch.id')->exists()) {
                return;
            }
            if (DB::table('exam_sessions')->where('code', 'L9WTL5')->exists()) {
                throw new RuntimeException('Review demo code is already in use; no account was created.');
            }

            $now = now();
            $teacher = DB::table('users')->insertGetId([
                'name' => 'Google Play Reviewer',
                'email' => 'play-reviewer@examroom.smkn4tpi.sch.id',
                'password' => '$2y$12$ZffqX6xt64Mdg.hcl7PVe.wqzS4oIH6o3tkfFbBq.O5sNw0T3qONS',
                'school_name' => 'ExamRoom Demo — Fictional School',
                'supervisor_pin_hash' => '$2y$12$5nRCCLHVALfMytwoBpdOyeFj.PtX7CoBHOLanVueDOjhv1exB0TJm',
                'require_managed_lock' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('exam_sessions')->insert([
                'id' => '310772c8-7ab4-4e78-a1e5-4c529695bc54',
                'user_id' => $teacher,
                'code' => 'L9WTL5',
                'title' => 'Google Play Review — Demo Exam',
                'url' => 'https://docs.google.com/forms/d/e/1FAIpQLSdS4MQ2FiqRk2MH_kss9zrvNSWR_fHKHlj3yJM1bDLVmXZ_nQ/viewform?usp=dialog',
                'platform' => 'Google Forms',
                'school_name' => 'ExamRoom Demo — Fictional School',
                'teacher_name' => 'Google Play Reviewer',
                'duration_minutes' => 120,
                'expires_at' => $now->copy()->addYears(5),
                'active' => true,
                'require_managed_lock' => false,
                'supervisor_pin_hash' => '$2y$12$5nRCCLHVALfMytwoBpdOyeFj.PtX7CoBHOLanVueDOjhv1exB0TJm',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        // Data-only provisioning: rollback must not delete an account or its sessions.
        // Remove the dedicated reviewer through the verified account deletion flow.
    }
};
