<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'jobs.view', 'jobs.create', 'jobs.edit', 'jobs.delete',
            'candidates.view', 'candidates.shortlist', 'candidates.reject',
            'applications.view', 'applications.manage',
            'ai.screen', 'ai.generate_questions',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'company.settings', 'company.billing',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        $hrAdmin = Role::firstOrCreate(['name' => 'hr_admin']);
        $hrAdmin->syncPermissions([
            'jobs.view', 'jobs.create', 'jobs.edit', 'jobs.delete',
            'candidates.view', 'candidates.shortlist', 'candidates.reject',
            'applications.view', 'applications.manage',
            'ai.screen', 'ai.generate_questions',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'company.settings', 'reports.view',
        ]);

        $hiringManager = Role::firstOrCreate(['name' => 'hiring_manager']);
        $hiringManager->syncPermissions([
            'jobs.view', 'candidates.view', 'applications.view',
        ]);

        $hrStandard = Role::firstOrCreate(['name' => 'hr_standard']);
        $hrStandard->syncPermissions([
            'jobs.view',
            'candidates.view',
            'applications.view',
            'reports.view',
        ]);

        Role::firstOrCreate(['name' => 'candidate']);
    }
}
