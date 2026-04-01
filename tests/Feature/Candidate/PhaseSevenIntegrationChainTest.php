<?php

use App\Jobs\ProcessCvAnalysis;
use App\Models\AiAnalysis;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EmailLog;
use App\Models\Interview;
use App\Models\JobListing;
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\InterviewScheduled;
use App\Services\AiCvAnalyserService;
use App\Services\CandidateDecisionEmailService;
use App\Services\InterviewSlotEngineService;
use App\Services\OpenAiRecruiterNoteService;
use App\Services\ScoreBasedProcessingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

dataset('phase7_chain_scores', [
    'score 30 rejected' => [30, 'rejected', 'rejection', 'candidate_rejection', false],
    'score 60 shortlisted' => [60, 'shortlisted', 'shortlist', 'candidate_shortlist', false],
    'score 85 interview + slot' => [85, 'interview', 'interview', 'candidate_interview', true],
]);

test(
    'phase 7 chain validates decision note email notification dashboard and slot booking',
    function (
        int $score,
        string $expectedStatus,
        string $expectedNoteType,
        string $expectedEmailTemplate,
        bool $expectsInterview
    ) {
        config(['queue.default' => 'sync']);
        Mail::fake();
        Notification::fake();

        $fixture = makePhaseSevenApplicationFixture();

        $aiService = Mockery::mock(AiCvAnalyserService::class);
        $aiService->shouldReceive('extractCvData')->never();
        $aiService->shouldReceive('analyse')
            ->once()
            ->andReturnUsing(function (Application $application) use ($score) {
                return AiAnalysis::query()->updateOrCreate(
                    ['application_id' => $application->id],
                    [
                        'candidate_id' => $application->candidate_id,
                        'job_listing_id' => $application->job_listing_id,
                        'match_score' => $score,
                        'matched_skills' => ['php', 'laravel'],
                        'missing_skills' => ['kubernetes'],
                        'reasoning' => "Phase 7 integration score {$score}",
                        'strengths' => 'Backend engineering depth.',
                        'weaknesses' => 'Limited platform exposure.',
                        'recommendation' => $score >= 71 ? 'yes' : 'maybe',
                    ]
                );
            });

        $noteService = Mockery::mock(OpenAiRecruiterNoteService::class);
        $noteService->shouldReceive('generateRecruiterNote')
            ->once()
            ->andReturn([
                'content' => "AI note for score {$score} in Phase 7 chain test.",
                'model' => 'gpt-5-mini',
                'usage' => ['input_tokens' => 22, 'output_tokens' => 18, 'total_tokens' => 40],
            ]);
        $this->app->instance(OpenAiRecruiterNoteService::class, $noteService);

        $job = new ProcessCvAnalysis($fixture['application']->fresh());
        $job->handle(
            $aiService,
            app(ScoreBasedProcessingEngine::class),
            app(CandidateDecisionEmailService::class),
            app(InterviewSlotEngineService::class)
        );

        $application = $fixture['application']->fresh(['candidate.user', 'notes']);
        $candidateUser = $fixture['candidateUser']->fresh();

        expect($application->status)->toBe($expectedStatus);
        expect((int) $application->ai_score)->toBe($score);
        expect($application->status_changed_at)->not->toBeNull();
        expect($application->recruiter_notes)->toContain("score {$score}");
        expect($application->candidate?->cv_status)->toBe('processed');

        $note = $application->notes()->latest('id')->first();
        expect($note)->not->toBeNull();
        expect($note?->note_type)->toBe($expectedNoteType);
        expect($note?->source)->toBe('ai');

        $emailLog = EmailLog::query()
            ->where('application_id', $application->id)
            ->where('template', $expectedEmailTemplate)
            ->latest('id')
            ->first();
        expect($emailLog)->not->toBeNull();
        expect((string) $emailLog?->status)->toBe('sent');
        expect((string) data_get($emailLog?->meta, 'decision'))->toBe($expectedStatus);
        expect((string) data_get($emailLog?->meta, 'note_content'))->toContain("score {$score}");

        Notification::assertSentTo($candidateUser, ApplicationStatusChanged::class);

        if ($expectsInterview) {
            $interview = Interview::query()
                ->where('application_id', $application->id)
                ->where('status', 'scheduled')
                ->latest('id')
                ->first();
            expect($interview)->not->toBeNull();
            expect($interview?->interview_slot_id)->not->toBeNull();

            $this->assertDatabaseHas('interview_slots', [
                'id' => $interview?->interview_slot_id,
                'booked_application_id' => $application->id,
                'is_available' => 0,
            ]);

            Notification::assertSentTo($candidateUser, InterviewScheduled::class);
        } else {
            $this->assertDatabaseMissing('interviews', [
                'application_id' => $application->id,
                'status' => 'scheduled',
            ]);
            Notification::assertNotSentTo($candidateUser, InterviewScheduled::class);
        }

        $this->actingAs($candidateUser)
            ->get(route('candidate.applications'))
            ->assertOk()
            ->assertSee($fixture['job']->title);
    }
)->with('phase7_chain_scores');

function makePhaseSevenApplicationFixture(): array
{
    foreach (['candidate', 'hr_admin'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }

    $company = Company::query()->create([
        'name' => 'Phase Seven QA Co',
        'slug' => 'phase-seven-qa-co',
        'email' => 'phase-seven-qa@example.com',
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

    $candidate = Candidate::query()->create([
        'user_id' => $candidateUser->id,
        'company_id' => $company->id,
        'name' => $candidateUser->name,
        'email' => $candidateUser->email,
        'cv_status' => 'pending',
        'cv_raw_text' => 'Laravel PHP APIs distributed systems',
    ]);

    $job = JobListing::query()->create([
        'company_id' => $company->id,
        'created_by' => $hr->id,
        'title' => 'Phase Seven Platform Engineer',
        'slug' => 'phase-seven-platform-engineer-' . uniqid(),
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => 'Build resilient hiring systems.',
        'status' => 'active',
        'published_at' => now()->subDay(),
    ]);

    $application = Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'applied',
    ]);

    return compact('company', 'hr', 'candidateUser', 'candidate', 'job', 'application');
}

