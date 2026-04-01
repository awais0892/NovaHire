<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('interviews', 'google_calendar_event_id')) {
                $table->string('google_calendar_event_id')->nullable()->after('meeting_link');
                $table->index('google_calendar_event_id');
            }

            if (!Schema::hasColumn('interviews', 'google_calendar_event_link')) {
                $table->string('google_calendar_event_link')->nullable()->after('google_calendar_event_id');
            }

            if (!Schema::hasColumn('interviews', 'google_calendar_synced_at')) {
                $table->timestamp('google_calendar_synced_at')->nullable()->after('google_calendar_event_link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            if (Schema::hasColumn('interviews', 'google_calendar_synced_at')) {
                $table->dropColumn('google_calendar_synced_at');
            }

            if (Schema::hasColumn('interviews', 'google_calendar_event_link')) {
                $table->dropColumn('google_calendar_event_link');
            }

            if (Schema::hasColumn('interviews', 'google_calendar_event_id')) {
                $table->dropIndex(['google_calendar_event_id']);
                $table->dropColumn('google_calendar_event_id');
            }
        });
    }
};

