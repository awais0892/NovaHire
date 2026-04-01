<?php

use App\Models\User;
use App\Notifications\TwoFactorOtpCode;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('candidate with two-factor enabled must verify otp code after password login', function () {
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => true,
        'email_verified_at' => now(),
    ]);
    $user->assignRole('candidate');

    Notification::fake();

    $response = $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('auth.2fa.challenge.show'));
    $this->assertGuest();

    $sentCode = null;
    Notification::assertSentTo(
        $user,
        TwoFactorOtpCode::class,
        function (TwoFactorOtpCode $notification) use (&$sentCode) {
            $sentCode = $notification->code;
            return true;
        }
    );

    expect($sentCode)->not->toBeNull();

    $this->post(route('auth.2fa.challenge.verify'), [
        'code' => $sentCode,
    ])->assertRedirect(route('candidate.applications'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->two_factor_code)->toBeNull();
    expect($user->fresh()->two_factor_code_expires_at)->toBeNull();
});

test('forgot password request creates reset token record and sends reset notification', function () {
    $user = User::factory()->create([
        'email' => 'recover@example.com',
        'status' => 'active',
    ]);

    Notification::fake();

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])->assertSessionHas('status');

    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeTrue();
    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset updates user password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => bcrypt('old-password'),
        'status' => 'active',
    ]);

    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});
