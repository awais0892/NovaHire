<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('hr admin can view application details and override recruiter note', function () {
    seedPhaseFiveApplicationPermissions();
    $data = makePhaseFiveApplicationData('hr_admin');

    $this->actingAs($data['user'])
        ->get(route('recruiter.applications'))
        ->assertOk();

    $this->actingAs($data['user'])
        ->getJson(route('recruiter.applications.details', $data['application']))
        ->assertOk()
        ->assertJsonPath('application.id', $data['application']->id);

    $this->actingAs($data['user'])
        ->postJson(route('recruiter.applications.notes.override', $data['application']), [
            'note_content' => 'Candidate has strong API design experience.',
            'decision' => 'shortlisted',
            'send_email' => false,
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    $this->assertDatabaseHas('applications', [
        'id' => $data['application']->id,
        'recruiter_notes' => 'Candidate has strong API design experience.',
    ]);

    $this->assertDatabaseHas('application_notes', [
        'application_id' => $data['application']->id,
        'source' => 'hr',
        'note_type' => 'hr_override',
    ]);
});

test('hr standard can view application dashboard but cannot override notes', function () {
    seedPhaseFiveApplicationPermissions();
    $data = makePhaseFiveApplicationData('hr_standard');

    $this->actingAs($data['user'])
        ->get(route('recruiter.applications'))
        ->assertOk();

    $this->actingAs($data['user'])
        ->getJson(route('recruiter.applications.details', $data['application']))
        ->assertOk();

    $this->actingAs($data['user'])
        ->postJson(route('recruiter.applications.notes.override', $data['application']), [
            'note_content' => 'Attempted unauthorized override.',
            'decision' => 'shortlisted',
            'send_email' => false,
        ])
        ->assertForbidden();
});

function seedPhaseFiveApplicationPermissions(): void
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Permission::firstOrCreate(['name' => 'applications.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'applications.manage', 'guard_name' => 'web']);

    $hrAdmin = Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $hrAdmin->syncPermissions(['applications.view', 'applications.manage']);

    $hrStandard = Role::firstOrCreate(['name' => 'hr_standard', 'guard_name' => 'web']);
    $hrStandard->syncPermissions(['applications.view']);
}

function makePhaseFiveApplicationData(string $role): array
{
    $company = Company::query()->create([
        'name' => 'Phase Five Co',
        'slug' => 'phase-five-co',
        'email' => fake()->unique()->safeEmail(),
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $user->assignRole($role);

    $candidateUser = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $candidate = Candidate::query()->create([
        'user_id' => $candidateUser->id,
        'company_id' => $company->id,
        'name' => $candidateUser->name,
        'email' => $candidateUser->email,
    ]);

    $job = JobListing::query()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'title' => 'Backend Engineer',
        'slug' => 'backend-engineer-' . uniqid(),
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => 'Build APIs and distributed services.',
        'status' => 'active',
    ]);

    $application = Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'shortlisted',
        'ai_score' => 82,
    ]);

    return compact('company', 'user', 'candidate', 'job', 'application');
}
