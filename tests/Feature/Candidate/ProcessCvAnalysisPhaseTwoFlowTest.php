<?php

use App\Jobs\ProcessCvAnalysis;
use App\Models\AiAnalysis;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EmailLog;
use App\Models\JobListing;
use App\Models\User;
use App\Services\AiCvAnalyserService;
use App\Services\CandidateDecisionEmailService;
use App\Services\InterviewSlotEngineService;
use App\Services\OpenAiRecruiterNoteService;
use App\Services\ScoreBasedProcessingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('process cv analysis job runs phase 2 decision and stores note', function () {
    $application = makeProcessFlowApplication();

    $aiService = Mockery::mock(AiCvAnalyserService::class);
    $aiService->shouldReceive('extractCvData')->never();
    $aiService->shouldReceive('analyse')
        ->once()
        ->andReturnUsing(function (Application $app) {
            return AiAnalysis::query()->updateOrCreate(
                ['application_id' => $app->id],
                [
                    'candidate_id' => $app->candidate_id,
                    'job_listing_id' => $app->job_listing_id,
                    'match_score' => 71,
                    'matched_skills' => ['php'],
                    'missing_skills' => ['go'],
                    'reasoning' => 'Strong overall fit.',
                    'strengths' => 'Solid backend experience.',
                    'weaknesses' => 'Limited distributed systems depth.',
                    'recommendation' => 'yes',
                ]
            );
        });

    $noteService = Mockery::mock(OpenAiRecruiterNoteService::class);
    $noteService->shouldReceive('generateRecruiterNote')
        ->once()
        ->andReturn([
            'content' => 'Candidate should move forward to interview scheduling.',
            'model' => 'gpt-4o-mini',
            'usage' => ['input_tokens' => 12, 'output_tokens' => 18, 'total_tokens' => 30],
        ]);
    $this->app->instance(OpenAiRecruiterNoteService::class, $noteService);

    $job = new ProcessCvAnalysis($application->fresh());
    $job->handle(
        $aiService,
        app(ScoreBasedProcessingEngine::class),
        app(CandidateDecisionEmailService::class),
        app(InterviewSlotEngineService::class)
    );

    $application->refresh();
    $candidate = $application->candidate()->first();

    expect($application->ai_score)->toBe(71);
    expect($application->status)->toBe('interview');
    expect($application->status_changed_at)->not->toBeNull();
    expect($application->notes()->count())->toBe(1);
    expect($application->notes()->first()?->note_type)->toBe('interview');
    expect($candidate?->cv_status)->toBe('processed');

    $emailLog = EmailLog::query()
        ->where('application_id', $application->id)
        ->where('template', 'candidate_interview')
        ->latest('id')
        ->first();
    expect($emailLog)->not->toBeNull();
    expect(in_array($emailLog?->status, ['queued', 'sent'], true))->toBeTrue();
});

function makeProcessFlowApplication(): Application
{
    $company = Company::query()->create([
        'name' => 'Process Flow Co',
        'slug' => 'process-flow-co',
        'email' => fake()->unique()->safeEmail(),
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
        'cv_raw_text' => 'PHP Laravel MySQL',
        'cv_status' => 'pending',
    ]);

    $job = JobListing::query()->create([
        'company_id' => $company->id,
        'created_by' => $recruiter->id,
        'title' => 'Senior Backend Engineer',
        'slug' => 'senior-backend-engineer-' . uniqid(),
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => 'Build scalable APIs.',
        'status' => 'active',
    ]);

    return Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => 'applied',
    ]);
}
