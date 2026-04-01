<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interviews')) {
            return;
        }

        Schema::table('interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('interviews', 'candidate_response')) {
                $table->string('candidate_response', 20)->nullable()->after('status');
            }
            if (!Schema::hasColumn('interviews', 'candidate_responded_at')) {
                $table->timestamp('candidate_responded_at')->nullable()->after('candidate_response');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('interviews')) {
            return;
        }

        Schema::table('interviews', function (Blueprint $table) {
            if (Schema::hasColumn('interviews', 'candidate_responded_at')) {
                $table->dropColumn('candidate_responded_at');
            }
            if (Schema::hasColumn('interviews', 'candidate_response')) {
                $table->dropColumn('candidate_response');
            }
        });
    }
};
