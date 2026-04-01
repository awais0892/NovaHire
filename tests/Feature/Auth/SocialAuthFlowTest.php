<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function socialAuthRole(string $name): void
{
    Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

function fakeGoogleUser(string $email, string $name, string $id): SocialiteUserContract
{
    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->shouldReceive('getName')->andReturn($name);
    $socialUser->shouldReceive('getId')->andReturn($id);

    return $socialUser;
}

function mockGoogleCallbackUser(SocialiteUserContract $socialUser): void
{
    $provider = Mockery::mock();
    $provider->shouldReceive('stateless')->once()->andReturnSelf();
    $provider->shouldReceive('user')->once()->andReturn($socialUser);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);
}

test('google callback creates a new candidate account and redirects to candidate profile', function () {
    socialAuthRole('candidate');

    mockGoogleCallbackUser(
        fakeGoogleUser('new.candidate@example.com', 'New Candidate', 'google-uid-001')
    );

    get(route('auth.google.callback'))
        ->assertRedirect(route('candidate.profile'));

    $user = User::where('email', 'new.candidate@example.com')->first();
    $candidate = Candidate::where('user_id', $user?->id)->first();
    expect($user)->not->toBeNull();
    expect($user?->google_id)->toBe('google-uid-001');
    expect($user?->hasRole('candidate'))->toBeTrue();
    expect($candidate)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('google callback denies non-candidate account with same email', function () {
    socialAuthRole('hr_admin');

    $company = Company::create([
        'name' => 'Auth Co',
        'slug' => 'auth-co',
        'email' => 'auth-co@example.com',
        'status' => 'active',
    ]);

    $hr = User::factory()->create([
        'email' => 'existing.hr@example.com',
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    $hr->assignRole('hr_admin');

    mockGoogleCallbackUser(
        fakeGoogleUser('existing.hr@example.com', 'Existing HR', 'google-uid-002')
    );

    get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($hr->fresh()->google_id)->toBeNull();
});
