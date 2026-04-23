<?php

use App\Models\Candidate;
use App\Models\Company;
use App\Models\User;
use App\Notifications\CandidateRegistrationVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createUserWithRole(string $roleName, ?int $companyId = null): User
{
    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'company_id' => $companyId,
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    $user->assignRole($roleName);

    return $user;
}

test('health endpoint returns ok payload', function () {
    $this->get('/health')
        ->assertOk()
        ->assertJsonStructure(['status', 'app', 'database', 'timestamp']);
});

test('register page is candidate only', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Candidate account only')
        ->assertSee('Secure onboarding')
        ->assertSee('name="name"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertDontSee('Enable 2-step verification')
        ->assertDontSee('Company Name');
});

test('self registration creates a candidate account', function () {
    Notification::fake();

    $this->post(route('register.post'), [
        'name' => 'Candidate Example',
        'email' => 'candidate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('register.verify.notice'));

    $this->assertGuest();

    $user = User::where('email', 'candidate@example.com')->first();
    $candidate = Candidate::where('user_id', $user?->id)->first();

    expect($user)->not->toBeNull();
    expect($user?->company_id)->toBeNull();
    expect($user?->hasRole('candidate'))->toBeTrue();
    expect($user?->email_verified_at)->toBeNull();
    expect($candidate)->not->toBeNull();
    expect($candidate?->email)->toBe('candidate@example.com');
    Notification::assertSentTo($user, CandidateRegistrationVerification::class);
});

test('unverified self-registered candidate must verify email before login', function () {
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'status' => 'active',
        'company_id' => null,
        'email_verified_at' => null,
    ]);
    $user->assignRole('candidate');

    Notification::fake();

    $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    Notification::assertSentTo($user, CandidateRegistrationVerification::class);
});

test('candidate verification link verifies account and signs in', function () {
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'status' => 'active',
        'company_id' => null,
        'email_verified_at' => null,
    ]);
    $user->assignRole('candidate');

    $url = URL::temporarySignedRoute(
        'register.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    $this->get($url)->assertRedirect(route('candidate.applications'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($user->fresh());
});

test('verification resend stays generic for unknown email addresses', function () {
    $response = $this->post(route('register.verify.resend'), [
        'email' => 'missing@example.com',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('status');
    $response->assertSessionDoesntHaveErrors();
});

test('super admin login redirects to admin dashboard', function () {
    $user = createUserWithRole('super_admin');

    $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));
});

test('hr admin login redirects to recruiter dashboard', function () {
    $company = Company::create([
        'name' => 'Acme HR',
        'slug' => 'acme-hr',
        'email' => 'acme-hr@example.com',
        'status' => 'active',
    ]);

    $user = createUserWithRole('hr_admin', $company->id);

    $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('recruiter.dashboard'));
});

test('candidate login redirects to candidate applications', function () {
    $user = createUserWithRole('candidate');

    $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('candidate.applications'));
});

test('invalid login shows validation error', function () {
    $this->post(route('login.post'), [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');
});
