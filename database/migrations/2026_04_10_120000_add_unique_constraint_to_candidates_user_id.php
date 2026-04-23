<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateUserIds = DB::table('candidates')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        if ($duplicateUserIds->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce the candidate user constraint because duplicate candidate rows already exist for user_ids: '
                . $duplicateUserIds->implode(', ')
            );
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->unique('user_id', 'candidates_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique('candidates_user_id_unique');
        });
    }
};
