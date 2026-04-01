<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles/permissions are seeded first
        $this->call(RolesAndPermissionsSeeder::class);

        $company = Company::firstOrCreate(
            ['slug' => 'testco'],
            [
                'name' => 'TestCo',
                'email' => 'contact@testco.dev',
                'plan' => 'pro',
                'status' => 'active',
            ]
        );

        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@test.com', 'role' => 'super_admin', 'company' => null],
            ['name' => 'HR Admin', 'email' => 'hr@test.com', 'role' => 'hr_admin', 'company' => $company->id],
            ['name' => 'HR Standard', 'email' => 'hr.standard@test.com', 'role' => 'hr_standard', 'company' => $company->id],
            ['name' => 'Recruiter', 'email' => 'recruiter@test.com', 'role' => 'hr_admin', 'company' => $company->id],
            ['name' => 'Hiring Manager', 'email' => 'manager@test.com', 'role' => 'hiring_manager', 'company' => $company->id],
            ['name' => 'Candidate', 'email' => 'candidate@test.com', 'role' => 'candidate', 'company' => $company->id],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'company_id' => $u['company'],
                    'status' => 'active',
                ]
            );
            $user->syncRoles([$u['role']]);
        }
    }
}
