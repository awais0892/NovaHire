<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NavigationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_recruiter_account_pages_are_available(): void
    {
        $company = Company::create([
            'name' => 'Acme Recruiting',
            'slug' => 'acme-recruiting',
            'email' => 'acme@example.com',
            'status' => 'active',
        ]);

        $role = Role::firstOrCreate(['name' => 'hr_admin']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('account.settings'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('recruiter.settings'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('recruiter.interview-slots.index'))
            ->assertOk();
    }

    public function test_hiring_manager_dashboard_links_to_existing_shortlist_page(): void
    {
        $company = Company::create([
            'name' => 'Acme Hiring',
            'slug' => 'acme-hiring',
            'email' => 'hiring@example.com',
            'status' => 'active',
        ]);

        $role = Role::firstOrCreate(['name' => 'hiring_manager']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee(route('manager.shortlisted'), false);

        $this->actingAs($user)
            ->get(route('manager.shortlisted'))
            ->assertOk();
    }
}
