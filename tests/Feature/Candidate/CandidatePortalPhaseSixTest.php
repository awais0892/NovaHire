<?php

use App\Livewire\Candidate\MyApplications;
use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EmailLog;
use App\Models\Interview;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function seedPhaseSixPortalRoles(): void
{
    foreach (['candidate', 'hr_admin'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
}

function buildPhaseSixCandidatePortalFixture(): array
{
    seedPhaseSixPortalRoles();

    $company = Company::create([
        'name' => 'Phase Six Labs',
        'slug' => 'phase-six-labs',
        'email' => 'phase-six@example.com',
        'status' => 'active',
    ]);

    $hr = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $hr->assignRole('hr_admin');

    $candidateUser = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $candidateUser->assignRole('candidate');

    $candidate = Candidate::create([
        'user_id' => $candidateUser->id,
        'company_id' => $company->id,
        'name' => $candidateUser->name,
        'email' => $candidateUser->email,
        'cv_status' => 'processed',
    ]);

    $job = JobListing::create([
        'company_id' => $company->id,
        'created_by' => $hr->id,
        'title' => 'Senior Platform Engineer',
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'salary_min' => 70000,
        'salary_max' => 95000,
        'salary_currency' => 'GBP',
        'salary_visible' => true,
        'description' => 'Platform engineering role.',
        'requirements' => 'APIs, distributed systems, cloud operations.',
        'benefits' => 'Hybrid working and growth budget.',
        'status' => 'active',
        'published_at' => now()->subDay(),
    ]);

    $application = Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'interview',
        'recruiter_notes' => 'Legacy recruiter note fallback.',
    ]);

    ApplicationNote::create([
        'company_id' => $company->id,
        'application_id' => $application->id,
        'candidate_id' => $candidate->id,
        'author_user_id' => null,
        'note_type' => 'shortlist',
        'source' => 'ai',
        'subject' => 'AI Screening Summary',
        'content' => 'Candidate demonstrates strong API design and production readiness.',
    ]);

    ApplicationNote::create([
        'company_id' => $company->id,
        'application_id' => $application->id,
        'candidate_id' => $candidate->id,
        'author_user_id' => $hr->id,
        'note_type' => 'hr_override',
        'source' => 'hr',
        'subject' => 'Recruiter Follow-up',
        'content' => 'Manual recruiter follow-up note: proceed to final panel.',
    ]);

    EmailLog::create([
        'company_id' => $company->id,
        'application_id' => $application->id,
        'candidate_id' => $candidate->id,
        'template' => 'decision_shortlisted',
        'channel' => 'email',
        'direction' => 'outbound',
        'recipient_email' => $candidate->email,
        'subject' => 'Application shortlist update',
        'provider' => 'resend',
        'status' => 'sent',
        'sent_at' => now()->subMinutes(20),
    ]);

    $interview = Interview::create([
        'company_id' => $company->id,
        'application_id' => $application->id,
        'scheduled_by' => $hr->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'timezone' => 'UTC',
        'mode' => 'video',
        'meeting_link' => 'https://meet.example.com/phase6',
        'notes' => 'Bring portfolio discussion points.',
        'status' => 'scheduled',
    ]);

    return compact('company', 'hr', 'candidateUser', 'candidate', 'job', 'application', 'interview');
}

test('candidate portal renders recruiter notes thread email history and interviewer details', function () {
    $fixture = buildPhaseSixCandidatePortalFixture();

    $this->actingAs($fixture['candidateUser'])
        ->get(route('candidate.applications'))
        ->assertOk()
        ->assertSee('Recruiter Notes Timeline')
        ->assertSee('Candidate demonstrates strong API design and production readiness.')
        ->assertSee('Manual recruiter follow-up note: proceed to final panel.')
        ->assertSee('Email History')
        ->assertSee('Application shortlist update')
        ->assertSee('Interviewer:')
        ->assertSee($fixture['hr']->name);
});

test('candidate applications livewire shows phase six portal sections', function () {
    $fixture = buildPhaseSixCandidatePortalFixture();

    Livewire::actingAs($fixture['candidateUser'])
        ->test(MyApplications::class)
        ->assertSee('Recruiter Notes Timeline')
        ->assertSee('Email History')
        ->assertSee($fixture['hr']->name)
        ->assertSee('Join Meeting');
});

test('candidate interview invitation includes interviewer details', function () {
    $fixture = buildPhaseSixCandidatePortalFixture();

    $this->actingAs($fixture['candidateUser'])
        ->get(route('candidate.interviews.invitation.show', $fixture['interview']))
        ->assertOk()
        ->assertSee('Interviewer')
        ->assertSee($fixture['hr']->name)
        ->assertSee('Join Interview');
});
