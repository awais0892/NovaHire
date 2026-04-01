<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('google_id');
            }

            if (!Schema::hasColumn('users', 'two_factor_code')) {
                $table->string('two_factor_code')->nullable()->after('two_factor_enabled');
            }

            if (!Schema::hasColumn('users', 'two_factor_code_expires_at')) {
                $table->timestamp('two_factor_code_expires_at')->nullable()->after('two_factor_code');
            }

            if (!Schema::hasColumn('users', 'two_factor_attempts')) {
                $table->unsignedTinyInteger('two_factor_attempts')->default(0)->after('two_factor_code_expires_at');
            }

            if (!Schema::hasColumn('users', 'two_factor_last_sent_at')) {
                $table->timestamp('two_factor_last_sent_at')->nullable()->after('two_factor_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'two_factor_last_sent_at')) {
                $table->dropColumn('two_factor_last_sent_at');
            }

            if (Schema::hasColumn('users', 'two_factor_attempts')) {
                $table->dropColumn('two_factor_attempts');
            }

            if (Schema::hasColumn('users', 'two_factor_code_expires_at')) {
                $table->dropColumn('two_factor_code_expires_at');
            }

            if (Schema::hasColumn('users', 'two_factor_code')) {
                $table->dropColumn('two_factor_code');
            }

            if (Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->dropColumn('two_factor_enabled');
            }
        });
    }
};

