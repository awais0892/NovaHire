<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adjust candidates unique constraints and indexes
        // Drop existing unique on email if present (execute immediately to allow failure without aborting migration)
        try {
            DB::statement('ALTER TABLE `candidates` DROP INDEX `candidates_email_unique`');
        } catch (\Throwable $e) {
            // ignore if it does not exist
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->unique(['company_id', 'email'], 'candidates_company_email_unique');
            $table->index(['user_id'], 'candidates_user_id_index');
        });

        // Helpful indexes for applications
        Schema::table('applications', function (Blueprint $table) {
            $table->index(['candidate_id'], 'applications_candidate_id_index');
            $table->index(['job_listing_id'], 'applications_job_listing_id_index');
            $table->index(['company_id'], 'applications_company_id_index');
            $table->index(['status'], 'applications_status_index');
        });
    }

    public function down(): void
    {
        // Revert applications indexes
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('applications_candidate_id_index');
            $table->dropIndex('applications_job_listing_id_index');
            $table->dropIndex('applications_company_id_index');
            $table->dropIndex('applications_status_index');
        });

        // Revert candidates unique indexes
        // Drop composite and restore single-email unique
        try {
            DB::statement('ALTER TABLE `candidates` DROP INDEX `candidates_company_email_unique`');
        } catch (\Throwable $e) {}

        Schema::table('candidates', function (Blueprint $table) {
            $table->unique('email', 'candidates_email_unique');
            $table->dropIndex('candidates_user_id_index');
        });
    }
};
