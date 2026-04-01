<?php

use App\Jobs\SendInterviewReminderNotifications;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\InterviewReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function interviewReminderSeed(): array
{
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $company = Company::create([
        'name' => 'Interview Co',
        'slug' => 'interview-co',
        'email' => 'interview-co@example.com',
        'status' => 'active',
        'plan' => 'pro',
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
        'company_id' => $company->id,
        'user_id' => $candidateUser->id,
        'name' => $candidateUser->name,
        'email' => $candidateUser->email,
    ]);

    $job = JobListing::create([
        'company_id' => $company->id,
        'created_by' => $hr->id,
        'title' => 'Senior Backend Engineer',
        'location' => 'Remote',
        'location_type' => 'remote',
        'job_type' => 'full_time',
        'description' => str_repeat('Backend architecture and API design required. ', 4),
        'status' => 'active',
        'vacancies' => 1,
    ]);

    $application = Application::create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'interview',
    ]);

    return compact('company', 'hr', 'candidateUser', 'candidate', 'job', 'application');
}

test('interview reminder job sends 24h reminder and marks timestamp', function () {
    Notification::fake();
    $data = interviewReminderSeed();

    Carbon::setTestNow(Carbon::parse('2026-03-08 09:00:00'));

    $interview = Interview::create([
        'company_id' => $data['company']->id,
        'application_id' => $data['application']->id,
        'scheduled_by' => $data['hr']->id,
        'starts_at' => now()->addHours(24),
        'ends_at' => now()->addHours(25),
        'timezone' => 'UTC',
        'mode' => 'video',
        'meeting_link' => 'https://meet.example.com/interview-24h',
        'status' => 'scheduled',
    ]);

    (new SendInterviewReminderNotifications())->handle();

    Notification::assertSentTo(
        $data['candidateUser'],
        InterviewReminder::class,
        fn(InterviewReminder $notification) => $notification->window === '24h'
            && $notification->interview->id === $interview->id
    );

    expect($interview->fresh()->reminder_24h_sent_at)->not->toBeNull();
    Carbon::setTestNow();
});

test('interview reminder job sends 1h reminder and marks timestamp', function () {
    Notification::fake();
    $data = interviewReminderSeed();

    Carbon::setTestNow(Carbon::parse('2026-03-08 09:00:00'));

    $interview = Interview::create([
        'company_id' => $data['company']->id,
        'application_id' => $data['application']->id,
        'scheduled_by' => $data['hr']->id,
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(2),
        'timezone' => 'UTC',
        'mode' => 'video',
        'meeting_link' => 'https://meet.example.com/interview-1h',
        'status' => 'scheduled',
    ]);

    (new SendInterviewReminderNotifications())->handle();

    Notification::assertSentTo(
        $data['candidateUser'],
        InterviewReminder::class,
        fn(InterviewReminder $notification) => $notification->window === '1h'
            && $notification->interview->id === $interview->id
    );

    expect($interview->fresh()->reminder_1h_sent_at)->not->toBeNull();
    Carbon::setTestNow();
});

