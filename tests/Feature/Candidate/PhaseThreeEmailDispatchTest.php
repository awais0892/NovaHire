<?php

use App\Jobs\SendDecisionEmailJob;
use App\Mail\CandidateDecisionMail;
use App\Mail\HrDailyEmailDigestMail;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EmailLog;
use App\Models\JobListing;
use App\Models\User;
use App\Services\CandidateDecisionEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('phase 3 queues tracked decision email log after score decision', function () {
    Queue::fake();
    $fixture = makePhaseThreeFixture();

    $application = $fixture['application'];
    $application->update([
        'status' => 'rejected',
        'recruiter_notes' => 'AI note for rejection scenario.',
    ]);

    $service = app(CandidateDecisionEmailService::class);
    $log = $service->queueForDecision($application->fresh(['candidate', 'jobListing.company']), 'rejected');

    expect($log)->not->toBeNull();
    expect($log?->status)->toBe('queued');
    expect($log?->template)->toBe('candidate_rejection');
    expect($log?->recipient_email)->toBe($fixture['candidate']->email);

    Queue::assertPushed(SendDecisionEmailJob::class, function (SendDecisionEmailJob $job) use ($log) {
        return $job->emailLogId === $log->id;
    });
});

test('phase 3 decision email job sends branded email with ai note and updates log status', function () {
    Mail::fake();
    $fixture = makePhaseThreeFixture();

    $application = $fixture['application'];
    $application->update([
        'status' => 'shortlisted',
        'recruiter_notes' => 'AI note for shortlist scenario with clear next steps.',
    ]);

    $log = EmailLog::query()->create([
        'company_id' => $fixture['company']->id,
        'application_id' => $application->id,
        'candidate_id' => $fixture['candidate']->id,
        'template' => 'candidate_shortlist',
        'channel' => 'email',
        'direction' => 'outbound',
        'recipient_email' => $fixture['candidate']->email,
        'subject' => 'NovaHire Shortlist Update',
        'provider' => (string) config('mail.default'),
        'status' => 'queued',
        'meta' => [
            'decision' => 'shortlisted',
            'note_content' => 'AI note for shortlist scenario with clear next steps.',
        ],
    ]);

    (new SendDecisionEmailJob($log->id))->handle();

    Mail::assertSent(CandidateDecisionMail::class, function (CandidateDecisionMail $mail) use ($fixture) {
        return $mail->hasTo($fixture['candidate']->email)
            && $mail->decision === 'shortlisted'
            && str_contains($mail->note, 'AI note for shortlist');
    });

    $log->refresh();
    expect($log->status)->toBe('sent');
    expect($log->sent_at)->not->toBeNull();
});

test('phase 3 decision email job uses retry configuration', function () {
    $job = new SendDecisionEmailJob(123);

    expect($job->tries)->toBe(3);
    expect($job->backoff())->toBe([60, 180, 540]);
});

test('phase 3 hr daily digest command sends summary email to hr admins', function () {
    Mail::fake();
    $fixture = makePhaseThreeFixture();

    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);
    $fixture['recruiter']->assignRole('hr_admin');

    EmailLog::query()->create([
        'company_id' => $fixture['company']->id,
        'application_id' => $fixture['application']->id,
        'candidate_id' => $fixture['candidate']->id,
        'template' => 'candidate_rejection',
        'channel' => 'email',
        'direction' => 'outbound',
        'recipient_email' => $fixture['candidate']->email,
        'subject' => 'NovaHire Update',
        'provider' => (string) config('mail.default'),
        'status' => 'sent',
        'sent_at' => now()->subHour(),
    ]);

    EmailLog::query()->create([
        'company_id' => $fixture['company']->id,
        'application_id' => $fixture['application']->id,
        'candidate_id' => $fixture['candidate']->id,
        'template' => 'candidate_shortlist',
        'channel' => 'email',
        'direction' => 'outbound',
        'recipient_email' => $fixture['candidate']->email,
        'subject' => 'NovaHire Shortlist Update',
        'provider' => (string) config('mail.default'),
        'status' => 'failed',
        'failed_at' => now()->subMinutes(30),
    ]);

    $this->artisan('recruitment:emails:digest')
        ->assertExitCode(0);

    Mail::assertSent(HrDailyEmailDigestMail::class, function (HrDailyEmailDigestMail $mail) use ($fixture) {
        return $mail->hasTo($fixture['recruiter']->email);
    });

    $digestLog = EmailLog::query()
        ->where('company_id', $fixture['company']->id)
        ->where('template', 'hr_daily_digest')
        ->where('recipient_email', $fixture['recruiter']->email)
        ->latest('id')
        ->first();

    expect($digestLog)->not->toBeNull();
    expect($digestLog?->status)->toBe('sent');
});

function makePhaseThreeFixture(): array
{
    $company = Company::query()->create([
        'name' => 'Phase Three Co',
        'slug' => 'phase-three-co',
        'email' => fake()->unique()->safeEmail(),
        'status' => 'active',
        'plan' => 'pro',
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
        'title' => 'Senior Platform Engineer',
        'slug' => 'senior-platform-engineer-' . uniqid(),
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => 'Build scalable platform systems.',
        'status' => 'active',
    ]);

    $application = Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'applied',
    ]);

    return compact('company', 'recruiter', 'candidate', 'application');
}

