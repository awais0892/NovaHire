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

function fakeGoogleUser(string $email, string $name, string $id, ?string $avatar = null): SocialiteUserContract
{
    $socialUser = Mockery::mock(SocialiteUserContract::class);
    $socialUser->shouldReceive('getEmail')->andReturn($email);
    $socialUser->shouldReceive('getName')->andReturn($name);
    $socialUser->shouldReceive('getId')->andReturn($id);
    $socialUser->shouldReceive('getAvatar')->andReturn($avatar);

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
    $googleAvatar = 'https://lh3.googleusercontent.com/a/avatar-new';

    mockGoogleCallbackUser(
        fakeGoogleUser('new.candidate@example.com', 'New Candidate', 'google-uid-001', $googleAvatar)
    );

    get(route('auth.google.callback'))
        ->assertRedirect(route('candidate.profile'));

    $user = User::where('email', 'new.candidate@example.com')->first();
    $candidate = Candidate::where('user_id', $user?->id)->first();
    expect($user)->not->toBeNull();
    expect($user?->google_id)->toBe('google-uid-001');
    expect($user?->avatar)->toBe($googleAvatar);
    expect($user?->hasRole('candidate'))->toBeTrue();
    expect($candidate)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('google callback updates candidate avatar when account has no avatar', function () {
    socialAuthRole('candidate');

    $candidateUser = User::factory()->create([
        'email' => 'candidate.without.avatar@example.com',
        'avatar' => null,
    ]);
    $candidateUser->assignRole('candidate');

    $googleAvatar = 'https://lh3.googleusercontent.com/a/avatar-update';
    mockGoogleCallbackUser(
        fakeGoogleUser(
            'candidate.without.avatar@example.com',
            'Candidate Without Avatar',
            'google-uid-003',
            $googleAvatar
        )
    );

    get(route('auth.google.callback'))
        ->assertRedirect(route('candidate.profile'));

    $candidateUser->refresh();
    expect($candidateUser->avatar)->toBe($googleAvatar);
    expect($candidateUser->google_id)->toBe('google-uid-003');
});

test('google callback does not overwrite custom avatar for existing candidate', function () {
    socialAuthRole('candidate');

    $candidateUser = User::factory()->create([
        'email' => 'candidate.custom.avatar@example.com',
        'avatar' => 'https://res.cloudinary.com/demo/image/upload/v1710000000/novahire/avatars/12/custom.jpg',
    ]);
    $candidateUser->assignRole('candidate');

    mockGoogleCallbackUser(
        fakeGoogleUser(
            'candidate.custom.avatar@example.com',
            'Candidate Custom Avatar',
            'google-uid-004',
            'https://lh3.googleusercontent.com/a/avatar-should-not-overwrite'
        )
    );

    get(route('auth.google.callback'))
        ->assertRedirect(route('candidate.profile'));

    $candidateUser->refresh();
    expect($candidateUser->avatar)
        ->toBe('https://res.cloudinary.com/demo/image/upload/v1710000000/novahire/avatars/12/custom.jpg');
    expect($candidateUser->google_id)->toBe('google-uid-004');
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
