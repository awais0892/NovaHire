<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('application status changed notification includes ai recruiter note in mail and payload', function () {
    $company = Company::query()->create([
        'name' => 'Notify Co',
        'slug' => 'notify-co',
        'email' => 'notify@example.com',
        'status' => 'active',
    ]);

    $recruiter = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

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
        'created_by' => $recruiter->id,
        'title' => 'Backend Engineer',
        'slug' => 'backend-engineer-note-' . uniqid(),
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => 'Build APIs and backend services.',
        'status' => 'active',
    ]);

    $application = Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'rejected',
        'recruiter_notes' => 'AI assessed the profile against job requirements and found key skill gaps in distributed systems and production API hardening.',
    ]);

    $notification = new ApplicationStatusChanged($application->fresh(['jobListing']));

    $payload = $notification->toArray($candidateUser);
    expect($payload['has_note'])->toBeTrue();
    expect((string) $payload['note_excerpt'])->toContain('AI assessed the profile');

    $mail = $notification->toMail($candidateUser);
    $mailLines = implode(' ', array_map(fn($line) => (string) $line, $mail->introLines));
    expect($mailLines)->toContain('AI Recruiter Note:');
    expect($mailLines)->toContain('AI assessed the profile');
});

