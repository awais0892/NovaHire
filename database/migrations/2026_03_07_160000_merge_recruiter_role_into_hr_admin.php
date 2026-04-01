<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $recruiterRoleId = DB::table('roles')->where('name', 'recruiter')->value('id');
        $hrAdminRoleId = DB::table('roles')->where('name', 'hr_admin')->value('id');

        if (!$recruiterRoleId || !$hrAdminRoleId) {
            return;
        }

        DB::statement(
            'INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
             SELECT ?, model_type, model_id
             FROM model_has_roles
             WHERE role_id = ?',
            [$hrAdminRoleId, $recruiterRoleId]
        );

        DB::table('model_has_roles')->where('role_id', $recruiterRoleId)->delete();
        DB::table('role_has_permissions')->where('role_id', $recruiterRoleId)->delete();
        DB::table('roles')->where('id', $recruiterRoleId)->delete();
    }

    public function down(): void
    {
        DB::table('roles')->insertOrIgnore([
            'name' => 'recruiter',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
