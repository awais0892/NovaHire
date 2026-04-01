<?php

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('hr cannot access recruiter application details from another company', function () {
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $companyA = Company::create([
        'name' => 'Company A',
        'slug' => 'company-a',
        'email' => 'company-a@example.com',
        'status' => 'active',
    ]);
    $companyB = Company::create([
        'name' => 'Company B',
        'slug' => 'company-b',
        'email' => 'company-b@example.com',
        'status' => 'active',
    ]);

    $hrA = User::factory()->create(['company_id' => $companyA->id, 'status' => 'active']);
    $hrA->assignRole('hr_admin');

    $hrB = User::factory()->create(['company_id' => $companyB->id, 'status' => 'active']);
    $hrB->assignRole('hr_admin');

    $candidateUser = User::factory()->create(['company_id' => $companyA->id, 'status' => 'active']);
    $candidate = Candidate::create([
        'user_id' => $candidateUser->id,
        'company_id' => $companyA->id,
        'name' => $candidateUser->name,
        'email' => $candidateUser->email,
    ]);

    $job = JobListing::create([
        'company_id' => $companyA->id,
        'created_by' => $hrA->id,
        'title' => 'Cross Company Security Test Role',
        'slug' => 'cross-company-security-test-role',
        'location' => 'London',
        'description' => 'Security test role.',
        'status' => 'active',
    ]);

    $application = Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $companyA->id,
        'status' => 'applied',
    ]);

    $this->actingAs($hrB)
        ->get(route('recruiter.applications.details', $application))
        ->assertNotFound();
});

test('recruiter applications search safely handles sql-like payloads', function () {
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $company = Company::create([
        'name' => 'Security Search Co',
        'slug' => 'security-search-co',
        'email' => 'security-search@example.com',
        'status' => 'active',
    ]);

    $hr = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $hr->assignRole('hr_admin');

    $candidateUser = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $candidate = Candidate::create([
        'user_id' => $candidateUser->id,
        'company_id' => $company->id,
        'name' => 'Alice Applicant',
        'email' => $candidateUser->email,
    ]);

    $job = JobListing::create([
        'company_id' => $company->id,
        'created_by' => $hr->id,
        'title' => 'Security Engineer',
        'slug' => 'security-engineer',
        'location' => 'London',
        'description' => 'Security role.',
        'status' => 'active',
    ]);

    Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'applied',
    ]);

    $this->actingAs($hr)
        ->get(route('recruiter.applications', ['q' => "' OR 1=1 -- "]))
        ->assertOk()
        ->assertSee('Applications Pipeline')
        ->assertDontSee('SQLSTATE');
});

test('candidate portal escapes recruiter notes and does not render executable scripts', function () {
    foreach (['candidate', 'hr_admin'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }

    $company = Company::create([
        'name' => 'XSS Guard Co',
        'slug' => 'xss-guard-co',
        'email' => 'xss-guard@example.com',
        'status' => 'active',
    ]);

    $hr = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $hr->assignRole('hr_admin');

    $candidateUser = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    $candidateUser->assignRole('candidate');

    $candidate = Candidate::create([
        'user_id' => $candidateUser->id,
        'company_id' => $company->id,
        'name' => $candidateUser->name,
        'email' => $candidateUser->email,
    ]);

    $job = JobListing::create([
        'company_id' => $company->id,
        'created_by' => $hr->id,
        'title' => 'Frontend Security Engineer',
        'slug' => 'frontend-security-engineer',
        'location' => 'Manchester',
        'description' => 'Security role.',
        'status' => 'active',
    ]);

    $application = Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'shortlisted',
    ]);

    ApplicationNote::create([
        'company_id' => $company->id,
        'application_id' => $application->id,
        'candidate_id' => $candidate->id,
        'author_user_id' => $hr->id,
        'note_type' => 'hr_override',
        'source' => 'hr',
        'subject' => 'Security review',
        'content' => '<script>alert("xss")</script> This is safe note text.',
    ]);

    $this->actingAs($candidateUser)
        ->get(route('candidate.applications'))
        ->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertSeeText('This is safe note text.');
});

