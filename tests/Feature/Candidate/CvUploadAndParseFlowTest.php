<?php

use App\Livewire\Candidate\CandidateProfile;
use App\Livewire\Candidates\CvUpload;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use App\Services\ResumeParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function candidateUserWithCompany(): array
{
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $company = Company::create([
        'name' => 'Talent Co',
        'slug' => 'talent-co',
        'email' => 'talent-co@example.com',
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

    return compact('company', 'hr', 'candidateUser');
}

function candidateUserWithoutCompany(): User
{
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

    $candidateUser = User::factory()->create([
        'company_id' => null,
        'status' => 'active',
    ]);
    $candidateUser->assignRole('candidate');

    return $candidateUser;
}

test('candidate profile cv upload parses and stores extracted fields', function () {
    Storage::fake('private');
    $data = candidateUserWithCompany();
    $this->actingAs($data['candidateUser']);

    $parser = Mockery::mock(ResumeParserService::class);
    $parser->shouldReceive('parsePdf')
        ->once()
        ->andReturn([
            'raw_text' => 'Experienced Laravel engineer',
            'name' => 'Candidate Parsed Name',
            'phone' => '+44 7000 000000',
            'location' => 'London',
            'linkedin' => 'https://linkedin.com/in/candidate',
            'github' => 'https://github.com/candidate',
            'portfolio' => 'https://candidate.dev',
            'skills' => ['laravel', 'php'],
            'experience' => [['title' => 'Backend Engineer']],
            'education' => [['degree' => 'BS Computer Science']],
        ]);
    $this->app->instance(ResumeParserService::class, $parser);

    Livewire::test(CandidateProfile::class)
        ->set('newCv', UploadedFile::fake()->create('resume.pdf', 200, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('cvUploaded', true)
        ->assertSet('saved', true);

    $candidate = Candidate::where('user_id', $data['candidateUser']->id)->first();
    expect($candidate)->not->toBeNull();
    expect($candidate?->cv_original_name)->toBe('resume.pdf');
    expect($candidate?->cv_status)->toBe('processed');
    expect($candidate?->extracted_skills)->toContain('laravel');
    expect($candidate?->phone)->toBe('+44 7000 000000');
});

test('candidate profile can upload and parse cv before any company is assigned', function () {
    Storage::fake('private');
    $candidateUser = candidateUserWithoutCompany();
    $this->actingAs($candidateUser);

    $parser = Mockery::mock(ResumeParserService::class);
    $parser->shouldReceive('parsePdf')
        ->once()
        ->andReturn([
            'raw_text' => 'Candidate based in Glasgow',
            'name' => 'Pre Application Candidate',
            'phone' => '+44 7700 900123',
            'location' => 'Glasgow',
            'linkedin' => 'https://linkedin.com/in/pre-application',
            'github' => 'https://github.com/pre-application',
            'portfolio' => 'https://pre-application.dev',
            'skills' => ['php'],
            'experience' => [],
            'education' => [],
        ]);
    $this->app->instance(ResumeParserService::class, $parser);

    Livewire::test(CandidateProfile::class)
        ->set('newCv', UploadedFile::fake()->create('candidate-profile.pdf', 180, 'application/pdf'))
        ->call('uploadResume')
        ->assertHasNoErrors()
        ->assertSet('cvUploaded', true)
        ->assertSet('location', 'Glasgow');

    $candidate = Candidate::where('user_id', $candidateUser->id)->first();
    expect($candidate)->not->toBeNull();
    expect($candidate?->company_id)->toBeNull();
    expect($candidate?->cv_original_name)->toBe('candidate-profile.pdf');
    expect($candidate?->cv_status)->toBe('processed');
});

test('candidate profile shows parsing error when uploaded pdf cannot be parsed', function () {
    Storage::fake('private');
    $data = candidateUserWithCompany();
    $this->actingAs($data['candidateUser']);

    $parser = Mockery::mock(ResumeParserService::class);
    $parser->shouldReceive('parsePdf')
        ->once()
        ->andThrow(new RuntimeException('Unreadable PDF'));
    $this->app->instance(ResumeParserService::class, $parser);

    Livewire::test(CandidateProfile::class)
        ->set('newCv', UploadedFile::fake()->create('broken-resume.pdf', 180, 'application/pdf'))
        ->call('uploadResume')
        ->assertHasErrors(['newCv'])
        ->assertSet('cvUploaded', false)
        ->assertSet('cvErrorMessage', 'Resume was uploaded, but parsing failed. Upload a text-based PDF and try again.');

    $candidate = Candidate::where('user_id', $data['candidateUser']->id)->first();
    expect($candidate)->not->toBeNull();
    expect($candidate?->cv_status)->toBe('failed');
});

test('candidate cv upload flow creates application and queues analysis path safely', function () {
    Storage::fake('private');
    config(['queue.default' => 'database']);

    $data = candidateUserWithCompany();

    $job = JobListing::create([
        'company_id' => $data['company']->id,
        'created_by' => $data['hr']->id,
        'title' => 'Platform Engineer',
        'location' => 'London',
        'location_type' => 'hybrid',
        'job_type' => 'full_time',
        'description' => str_repeat('Strong backend engineering experience. ', 4),
        'status' => 'active',
        'vacancies' => 1,
    ]);

    $this->actingAs($data['candidateUser']);

    Livewire::test(CvUpload::class, ['job' => $job->id])
        ->set('cvFile', UploadedFile::fake()->create('platform-cv.pdf', 180, 'application/pdf'))
        ->set('coverLetter', 'I am a strong fit for this role.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('isSubmitted', true)
        ->assertSet('alreadyApplied', false);

    $candidate = Candidate::where('user_id', $data['candidateUser']->id)->first();
    expect($candidate)->not->toBeNull();
    expect($candidate?->cv_status)->toBe('pending');

    $application = Application::where('candidate_id', $candidate->id)
        ->where('job_listing_id', $job->id)
        ->first();
    expect($application)->not->toBeNull();
    expect($application?->status)->toBe('applied');
});
