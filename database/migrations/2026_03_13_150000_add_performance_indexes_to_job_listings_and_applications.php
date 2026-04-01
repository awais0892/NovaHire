<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->index(['status', 'published_at'], 'job_listings_status_published_at_index');
            $table->index(['company_id', 'status', 'published_at'], 'job_listings_company_status_published_at_index');
            $table->index(['status', 'location'], 'job_listings_status_location_index');
            $table->index(['status', 'job_type'], 'job_listings_status_job_type_index');
            $table->index(['status', 'location_type'], 'job_listings_status_location_type_index');
            $table->index(['status', 'experience_level'], 'job_listings_status_experience_level_index');
            $table->index(['status', 'salary_min'], 'job_listings_status_salary_min_index');
            $table->index(['status', 'salary_max'], 'job_listings_status_salary_max_index');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index(['candidate_id', 'job_listing_id'], 'applications_candidate_job_listing_index');
            $table->index(['company_id', 'status', 'created_at'], 'applications_company_status_created_at_index');
            $table->index(['job_listing_id', 'status'], 'applications_job_listing_status_index');
            $table->index(['company_id', 'ai_score'], 'applications_company_ai_score_index');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('applications_candidate_job_listing_index');
            $table->dropIndex('applications_company_status_created_at_index');
            $table->dropIndex('applications_job_listing_status_index');
            $table->dropIndex('applications_company_ai_score_index');
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropIndex('job_listings_status_published_at_index');
            $table->dropIndex('job_listings_company_status_published_at_index');
            $table->dropIndex('job_listings_status_location_index');
            $table->dropIndex('job_listings_status_job_type_index');
            $table->dropIndex('job_listings_status_location_type_index');
            $table->dropIndex('job_listings_status_experience_level_index');
            $table->dropIndex('job_listings_status_salary_min_index');
            $table->dropIndex('job_listings_status_salary_max_index');
        });
    }
};
