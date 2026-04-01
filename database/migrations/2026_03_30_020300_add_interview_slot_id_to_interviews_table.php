<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('interviews', 'interview_slot_id')) {
            Schema::table('interviews', function (Blueprint $table) {
                $table->foreignId('interview_slot_id')
                    ->nullable()
                    ->after('application_id')
                    ->constrained('interview_slots')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('interviews', 'interview_slot_id')) {
            Schema::table('interviews', function (Blueprint $table) {
                $table->dropConstrainedForeignId('interview_slot_id');
            });
        }
    }
};
