<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeUser(string $role, ?int $companyId = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

    $user = User::factory()->create([
        'company_id' => $companyId,
        'status' => 'active',
    ]);
    $user->assignRole($role);

    return $user;
}

test('guest is redirected to login for protected recruiter route', function () {
    $this->get(route('recruiter.dashboard'))
        ->assertRedirect(route('login'));
});

test('candidate cannot access recruiter dashboard', function () {
    $candidate = makeUser('candidate');

    $this->actingAs($candidate)
        ->get(route('recruiter.dashboard'))
        ->assertForbidden();
});

test('hr admin can access recruiter dashboard', function () {
    $company = Company::create([
        'name' => 'Access Co',
        'slug' => 'access-co',
        'email' => 'access@example.com',
        'status' => 'active',
    ]);

    $user = makeUser('hr_admin', $company->id);

    $this->actingAs($user)
        ->get(route('recruiter.dashboard'))
        ->assertOk();
});

test('hr admin cannot access admin users page', function () {
    $company = Company::create([
        'name' => 'No Admin Co',
        'slug' => 'no-admin-co',
        'email' => 'no-admin@example.com',
        'status' => 'active',
    ]);

    $user = makeUser('hr_admin', $company->id);

    $this->actingAs($user)
        ->get(route('admin.users'))
        ->assertForbidden();
});
