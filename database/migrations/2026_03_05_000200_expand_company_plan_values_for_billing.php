<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE companies MODIFY COLUMN `plan` VARCHAR(32) NOT NULL DEFAULT 'free'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE companies MODIFY COLUMN `plan` ENUM('free','basic','pro','enterprise') NOT NULL DEFAULT 'free'");
    }
};
