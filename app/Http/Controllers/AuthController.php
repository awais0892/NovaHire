<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\CandidateRegistrationVerification;
use App\Notifications\TwoFactorOtpCode;
use App\Services\Auth\CandidateOnboardingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Support\AuditLogger;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const TWO_FACTOR_CODE_TTL_MINUTES = 10;
    private const TWO_FACTOR_MAX_ATTEMPTS = 5;
    private const TWO_FACTOR_RESEND_COOLDOWN_SECONDS = 45;
    private ?string $lastMailDispatchError = null;

    public function __construct(
        private readonly CandidateOnboardingService $candidateOnboarding,
    ) {}

    public function showLogin()
    {
        return view('pages.auth.signin', ['title' => 'Sign In']);
    }

    public function showRegister(): View
    {
        return view('pages.auth.register', ['title' => 'Create Account']);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validated['email'] = Str::lower(trim((string) $validated['email']));

        try {
            $user = $this->candidateOnboarding->createCandidateUser([
                'name' => trim((string) $validated['name']),
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status' => 'active',
                'two_factor_enabled' => false,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withErrors([
                    'email' => 'Unable to create your account right now. Please try again.',
                ])
                ->onlyInput('name', 'email');
        }

        $verificationSent = $this->dispatchRegistrationVerification($user);
        $request->session()->put('auth.registration_verification_email', $user->email);

        AuditLogger::log('auth.register.success', $user, [
            'email' => $user->email,
            'verification_sent' => $verificationSent,
        ]);

        if (!$verificationSent) {
            return redirect()->route('register.verify.notice')
                ->withErrors([
                    'email' => $this->lastMailDispatchError ?? 'Account created, but we could not start email verification right now. Please use resend from the next screen.',
                ]);
        }

        return redirect()->route('register.verify.notice')
            ->with('status', 'Your account was created. We sent a verification link to your email.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        $credentials['email'] = Str::lower(trim((string) $credentials['email']));

        $remember = (bool) $request->boolean('remember');

        \Log::info('Login attempt', ['email' => $credentials['email']]);

        if (Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'status' => 'active',
        ], $remember)) {
            $user = Auth::user();

            if ($user && $this->requiresRegistrationVerification($user)) {
                $this->dispatchRegistrationVerification($user);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $request->session()->put('auth.registration_verification_email', $user->email);

                return back()->withErrors([
                    'email' => 'Please verify your email before signing in. A verification link has been sent.',
                ])->onlyInput('email');
            }

            if ($user && $this->requiresTwoFactorChallenge($user)) {
                try {
                    $this->issueTwoFactorCode($user);
                } catch (\Throwable $exception) {
                    \Log::warning('Two-factor code dispatch failed at login.', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $exception->getMessage(),
                    ]);

                    Auth::logout();
                    return back()->withErrors([
                        'email' => $this->humanizeMailDispatchError(
                            $exception,
                            'Unable to send verification code right now. Please try again.'
                        ),
                    ])->onlyInput('email');
                }

                Auth::logout();
                $request->session()->regenerate();
                $request->session()->put('auth.2fa.user_id', $user->id);
                $request->session()->put('auth.2fa.remember', $remember);

                return redirect()->route('auth.2fa.challenge.show');
            }

            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();
            AuditLogger::log('auth.login.success', $user, [
                'email' => $user->email,
            ]);

            \Log::info('Login success', ['email' => $user->email, 'roles' => $user->getRoleNames()]);
            $defaultRoute = '/';

            if ($user->hasRole('super_admin')) {
                $defaultRoute = route('admin.dashboard');
            }
            if ($user->hasRole('hr_admin')) {
                $defaultRoute = route('recruiter.dashboard');
            }
            if ($user->hasRole('hr_standard')) {
                $defaultRoute = route('recruiter.applications');
            }
            if ($user->hasRole('hiring_manager')) {
                $defaultRoute = route('manager.dashboard');
            }
            if ($user->hasRole('candidate')) {
                $defaultRoute = route('candidate.applications');
            }

            return redirect()->intended($defaultRoute);
        }

        \Log::warning('Login failed', ['email' => $credentials['email']]);
        AuditLogger::log('auth.login.failed', null, [
            'email' => $credentials['email'],
        ]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegistrationVerificationNotice(Request $request): View|RedirectResponse
    {
        $email = (string) $request->session()->get('auth.registration_verification_email', '');
        if ($email === '') {
            return redirect()->route('login');
        }

        return view('pages.auth.register-verify', [
            'title' => 'Verify Email',
            'email' => $email,
            'maskedEmail' => $this->maskEmail($email),
        ]);
    }

    public function resendRegistrationVerification(Request $request): RedirectResponse
    {
        $email = (string) $request->session()->get('auth.registration_verification_email', '');
        $fromSession = $email !== '';

        if ($email === '') {
            $validated = $request->validate([
                'email' => ['required', 'email'],
            ]);
            $email = Str::lower(trim((string) $validated['email']));
        }

        $user = User::query()->where('email', $email)->first();

        if ($fromSession && $user && $user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Email already verified. Please sign in.');
        }

        if ($user && !$user->hasVerifiedEmail()) {
            if (!$this->dispatchRegistrationVerification($user)) {
                return back()->withErrors([
                    'email' => $this->lastMailDispatchError ?? 'Unable to resend verification email right now. Please try again.',
                ]);
            }

            $request->session()->put('auth.registration_verification_email', $user->email);
        }

        return back()->with(
            'status',
            'If an unverified account exists for ' . $this->maskEmail($email) . ', we sent a fresh verification link.'
        );
    }

    public function verifyRegistrationEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);
        if (!$user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Invalid verification link.',
            ]);
        }

        if (!hash_equals((string) $hash, sha1((string) $user->getEmailForVerification()))) {
            return redirect()->route('login')->withErrors([
                'email' => 'Invalid verification link.',
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('auth.registration_verification_email');

        AuditLogger::log('auth.register.email_verified', $user, [
            'email' => $user->email,
        ]);

        return redirect()->intended($this->defaultRouteFor($user))
            ->with('status', 'Email verified successfully.');
    }

    public function showTwoFactorChallenge(Request $request): View|RedirectResponse
    {
        $user = $this->resolvePendingTwoFactorUser($request);
        if (!$user) {
            return redirect()->route('login');
        }

        return view('pages.auth.two-factor-challenge', [
            'title' => 'Verify Login',
            'email' => $this->maskEmail($user->email),
        ]);
    }

    public function verifyTwoFactorChallenge(Request $request): RedirectResponse
    {
        $user = $this->resolvePendingTwoFactorUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Your login session expired. Please sign in again.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (!$user->two_factor_enabled) {
            $this->clearTwoFactorState($request, $user);
            return redirect()->route('login')
                ->withErrors(['email' => 'Two-step verification is not active for this account. Please sign in again.']);
        }

        if (!$user->two_factor_code || !$user->two_factor_code_expires_at || now()->greaterThan($user->two_factor_code_expires_at)) {
            $this->clearTwoFactorState($request, $user);
            return redirect()->route('login')
                ->withErrors(['email' => 'Verification code expired. Please sign in again.']);
        }

        if ((int) $user->two_factor_attempts >= self::TWO_FACTOR_MAX_ATTEMPTS) {
            $this->clearTwoFactorState($request, $user);
            return redirect()->route('login')
                ->withErrors(['email' => 'Too many invalid verification attempts. Please sign in again.']);
        }

        if (!Hash::check((string) $validated['code'], (string) $user->two_factor_code)) {
            $user->increment('two_factor_attempts');
            return back()->withErrors([
                'code' => 'Invalid verification code. Please try again.',
            ]);
        }

        $user->forceFill([
            'two_factor_code' => null,
            'two_factor_code_expires_at' => null,
            'two_factor_attempts' => 0,
            'last_login_at' => now(),
        ])->save();

        $remember = (bool) $request->session()->pull('auth.2fa.remember', false);
        Auth::login($user, $remember);
        $request->session()->forget('auth.2fa.user_id');
        $request->session()->regenerate();

        AuditLogger::log('auth.login.success', $user, [
            'email' => $user->email,
            'via_2fa' => true,
        ]);

        return redirect()->intended($this->defaultRouteFor($user));
    }

    public function resendTwoFactorChallenge(Request $request): RedirectResponse
    {
        $user = $this->resolvePendingTwoFactorUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Your login session expired. Please sign in again.']);
        }

        if ($user->two_factor_last_sent_at && now()->diffInSeconds($user->two_factor_last_sent_at) < self::TWO_FACTOR_RESEND_COOLDOWN_SECONDS) {
            $remaining = self::TWO_FACTOR_RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($user->two_factor_last_sent_at);
            return back()->withErrors([
                'code' => "Please wait {$remaining} seconds before requesting another code.",
            ]);
        }

        try {
            $this->issueTwoFactorCode($user);
        } catch (\Throwable $exception) {
            \Log::warning('Two-factor code resend failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'code' => $this->humanizeMailDispatchError(
                    $exception,
                    'Unable to resend code right now. Please try again.'
                ),
            ]);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function showForgotPasswordForm(): View
    {
        return view('pages.auth.forgot-password', ['title' => 'Forgot Password']);
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink([
                'email' => (string) $validated['email'],
            ]);
        } catch (\Throwable $exception) {
            \Log::warning('Password reset link dispatch failed.', [
                'email' => (string) $validated['email'],
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'email' => $this->humanizeMailDispatchError(
                    $exception,
                    'Unable to send password reset link right now. Please try again.'
                ),
            ])->onlyInput('email');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors([
            'email' => __($status),
        ])->onlyInput('email');
    }

    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('pages.auth.reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'email' => (string) $validated['email'],
                'password' => (string) $validated['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => (string) $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', __($status));
        }

        return back()->withErrors([
            'email' => [__($status)],
        ]);
    }

    public function logout(Request $request)
    {
        AuditLogger::log('auth.logout', Auth::user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function requiresTwoFactorChallenge(User $user): bool
    {
        return (bool) $user->two_factor_enabled && $user->hasRole('candidate');
    }

    private function requiresRegistrationVerification(User $user): bool
    {
        return $user->hasRole('candidate')
            && $user->company_id === null
            && !$user->hasVerifiedEmail();
    }

    private function issueTwoFactorCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_code_expires_at' => now()->addMinutes(self::TWO_FACTOR_CODE_TTL_MINUTES),
            'two_factor_attempts' => 0,
            'two_factor_last_sent_at' => now(),
        ])->save();

        $user->notify(new TwoFactorOtpCode($code, self::TWO_FACTOR_CODE_TTL_MINUTES));
    }

    private function dispatchRegistrationVerification(User $user): bool
    {
        $this->lastMailDispatchError = null;

        try {
            $user->notify(new CandidateRegistrationVerification());
            return true;
        } catch (\Throwable $exception) {
            \Log::warning('Registration verification email dispatch failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            $this->lastMailDispatchError = 'We could not send the verification email right now. Please try again in a moment.';

            return false;
        }
    }

    private function humanizeMailDispatchError(\Throwable $exception, string $fallback): string
    {
        $message = $exception->getMessage();
        $messageLower = strtolower($message);

        if (
            str_contains($messageLower, 'username and password not accepted')
            || str_contains($messageLower, 'badcredentials')
            || str_contains($messageLower, 'failed to authenticate on smtp server')
        ) {
            $smtpHost = strtolower((string) config('mail.mailers.smtp.host', ''));

            if (
                str_contains($smtpHost, 'brevo')
                || str_contains($messageLower, 'sendinblue')
                || str_contains($messageLower, 'smtpsib')
            ) {
                return 'Mail server authentication failed. For Brevo SMTP, set MAIL_HOST=smtp-relay.brevo.com, MAIL_USERNAME to your Brevo SMTP login email, and MAIL_PASSWORD to a Brevo SMTP key (not an API key).';
            }

            if (str_contains($smtpHost, 'gmail') || str_contains($messageLower, 'gmail')) {
                return 'Mail server authentication failed. For Gmail SMTP, set MAIL_PASSWORD to a Google App Password (16-character), not your normal Gmail password.';
            }

            return 'Mail server authentication failed. Verify MAIL_HOST, MAIL_PORT, MAIL_USERNAME, and MAIL_PASSWORD in your .env file.';
        }

        if (
            str_contains($messageLower, 'sender not allowed')
            || str_contains($messageLower, 'unauthorized sender')
            || str_contains($messageLower, 'sender address rejected')
        ) {
            return 'Sender address rejected by mail provider. In Brevo, verify MAIL_FROM_ADDRESS as a sender and authenticate your domain.';
        }

        if (str_contains($messageLower, 'resend api failed')) {
            return 'Mail provider rejected the request. Verify your sending domain and sender address in Resend before sending to external recipients.';
        }

        return $fallback;
    }

    private function resolvePendingTwoFactorUser(Request $request): ?User
    {
        $userId = $request->session()->get('auth.2fa.user_id');
        if (!$userId) {
            return null;
        }

        return User::query()->find((int) $userId);
    }

    private function clearTwoFactorState(Request $request, User $user): void
    {
        $user->forceFill([
            'two_factor_code' => null,
            'two_factor_code_expires_at' => null,
            'two_factor_attempts' => 0,
        ])->save();

        $request->session()->forget('auth.2fa.user_id');
        $request->session()->forget('auth.2fa.remember');
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email, 2);
        if ($name === '') {
            return '***@' . $domain;
        }

        $visible = mb_substr($name, 0, 1);
        return $visible . str_repeat('*', max(2, mb_strlen($name) - 1)) . '@' . $domain;
    }

    private function defaultRouteFor(User $user): string
    {
        if ($user->hasRole('super_admin')) {
            return route('admin.dashboard');
        }
        if ($user->hasRole('hr_admin')) {
            return route('recruiter.dashboard');
        }
        if ($user->hasRole('hr_standard')) {
            return route('recruiter.applications');
        }
        if ($user->hasRole('hiring_manager')) {
            return route('manager.dashboard');
        }
        if ($user->hasRole('candidate')) {
            return route('candidate.applications');
        }

        return '/';
    }
}
