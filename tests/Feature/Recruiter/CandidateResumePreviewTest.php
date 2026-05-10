<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

test('recruiter can stream a stored candidate resume inline', function () {
    Storage::fake('private');

    Role::firstOrCreate(['name' => 'hr_admin', 'guard_name' => 'web']);

    $company = Company::create([
        'name' => 'NovaHire Test Co',
        'slug' => 'novahire-test-co',
        'email' => 'hr@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]);

    $recruiter = User::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $recruiter->assignRole('hr_admin');

    $candidate = Candidate::create([
        'company_id' => $company->id,
        'name' => 'Awais Hassan',
        'email' => 'awais@example.com',
        'cv_path' => 'cvs/preview/awais-hassan.pdf',
        'cv_original_name' => 'awais-hassan.pdf',
        'cv_status' => 'processed',
    ]);

    Storage::disk('private')->put($candidate->cv_path, '%PDF-1.4 inline preview test');

    $response = $this->actingAs($recruiter)->get(
        route('recruiter.candidates.resume.download', [
            'candidate' => $candidate->id,
            'disposition' => 'inline',
        ])
    );

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    $response->assertHeader('x-frame-options', 'SAMEORIGIN');

    expect((string) $response->headers->get('content-disposition'))->toContain('inline');
    expect((string) $response->headers->get('content-disposition'))->toContain('awais-hassan.pdf');
});
