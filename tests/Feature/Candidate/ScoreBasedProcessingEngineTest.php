<?php

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use App\Services\OpenAiRecruiterNoteService;
use App\Services\ScoreBasedProcessingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('phase2_boundaries', [
    'score 50 rejected' => [50, 'rejected', 'rejection'],
    'score 51 shortlisted' => [51, 'shortlisted', 'shortlist'],
    'score 70 shortlisted' => [70, 'shortlisted', 'shortlist'],
    'score 71 interview' => [71, 'interview', 'interview'],
]);

test('phase 2 decision engine applies score boundaries and stores ai note', function (
    int $score,
    string $expectedStatus,
    string $expectedNoteType
) {
    $application = makePhaseTwoApplication('applied');

    $mock = Mockery::mock(OpenAiRecruiterNoteService::class);
    $mock->shouldReceive('generateRecruiterNote')
        ->once()
        ->andReturn([
            'content' => "Auto note for score {$score}",
            'model' => 'gpt-4o-mini',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20, 'total_tokens' => 30],
        ]);
    $this->app->instance(OpenAiRecruiterNoteService::class, $mock);

    $result = app(ScoreBasedProcessingEngine::class)->process($application, $score);
    $application->refresh();

    expect($result['score'])->toBe($score);
    expect($result['status_applied'])->toBeTrue();
    expect($result['status_after'])->toBe($expectedStatus);
    expect($result['note_created'])->toBeTrue();
    expect($application->status)->toBe($expectedStatus);
    expect($application->ai_score)->toBe($score);
    expect($application->status_changed_at)->not->toBeNull();
    expect($application->recruiter_notes)->toBe("Auto note for score {$score}");

    $note = $application->notes()->latest('id')->first();
    expect($note)->not->toBeNull();
    expect($note?->note_type)->toBe($expectedNoteType);
    expect($note?->source)->toBe('ai');
    expect((int) data_get($note?->meta, 'score'))->toBe($score);
    expect((string) data_get($note?->meta, 'decision'))->toBe($expectedStatus);
})->with('phase2_boundaries');

test('phase 2 decision engine does not override final statuses', function () {
    $application = makePhaseTwoApplication('hired');

    $mock = Mockery::mock(OpenAiRecruiterNoteService::class);
    $mock->shouldReceive('generateRecruiterNote')->never();
    $this->app->instance(OpenAiRecruiterNoteService::class, $mock);

    $result = app(ScoreBasedProcessingEngine::class)->process($application, 32);
    $application->refresh();

    expect($result['status_applied'])->toBeFalse();
    expect($result['status_before'])->toBe('hired');
    expect($result['status_after'])->toBe('hired');
    expect($result['note_created'])->toBeFalse();
    expect($application->status)->toBe('hired');
    expect($application->ai_score)->toBe(32);
    expect($application->notes()->count())->toBe(0);
});

function makePhaseTwoApplication(string $status): Application
{
    $company = Company::query()->create([
        'name' => 'Phase Two Co',
        'slug' => 'phase-two-co',
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
        'title' => 'Platform Engineer',
        'slug' => 'platform-engineer-' . uniqid(),
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => 'Build reliable hiring systems.',
        'status' => 'active',
    ]);

    return Application::query()->create([
        'job_listing_id' => $job->id,
        'candidate_id' => $candidate->id,
        'company_id' => $company->id,
        'status' => $status,
        'ai_score' => null,
    ]);
}

