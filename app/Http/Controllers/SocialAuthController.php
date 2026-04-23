<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\CandidateOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly CandidateOnboardingService $candidateOnboarding,
    ) {}

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in failed. Please try again.',
            ]);
        }

        $googleEmail = Str::lower(trim((string) $googleUser->getEmail()));

        if ($googleEmail === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Google account email is required.',
            ]);
        }

        $user = User::where('email', $googleEmail)->first();

        if ($user && ! $user->hasRole('candidate')) {
            return redirect()->route('login')->withErrors([
                'email' => 'This Google sign-in is only for candidate accounts.',
            ]);
        }

        if ($user && (string) ($user->status ?? 'active') !== 'active') {
            return redirect()->route('login')->withErrors([
                'email' => 'This account is not active. Please contact support.',
            ]);
        }

        $hasGoogleIdColumn = Schema::hasColumn('users', 'google_id');
        $googleAvatar = $this->resolveGoogleAvatarUrl($googleUser->getAvatar());

        if (! $user) {
            $attributes = [
                'name' => $googleUser->getName() ?: 'Candidate',
                'email' => $googleEmail,
                'password' => Str::password(32),
                'email_verified_at' => now(),
                'status' => 'active',
            ];
            if ($hasGoogleIdColumn) {
                $attributes['google_id'] = $googleUser->getId();
            }
            if ($googleAvatar !== null) {
                $attributes['avatar'] = $googleAvatar;
            }

            $user = $this->candidateOnboarding->createCandidateUser($attributes);
        } else {
            $updates = [];

            if ($hasGoogleIdColumn && empty($user->google_id)) {
                $updates['google_id'] = $googleUser->getId();
            }

            if ($this->shouldSyncGoogleAvatar($user, $googleAvatar)) {
                $updates['avatar'] = $googleAvatar;
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }

            $user = $this->candidateOnboarding->syncCandidateAccount($user);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user, true);

        return redirect()->route('candidate.profile');
    }

    private function resolveGoogleAvatarUrl(?string $avatar): ?string
    {
        $avatar = trim((string) $avatar);
        if ($avatar === '') {
            return null;
        }

        return filter_var($avatar, FILTER_VALIDATE_URL) ? $avatar : null;
    }

    private function shouldSyncGoogleAvatar(User $user, ?string $googleAvatar): bool
    {
        if ($googleAvatar === null) {
            return false;
        }

        $currentAvatar = trim((string) ($user->avatar ?? ''));
        if ($currentAvatar === '') {
            return true;
        }

        return $this->isGoogleAvatarUrl($currentAvatar);
    }

    private function isGoogleAvatarUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        return str_contains($host, 'googleusercontent.com')
            || str_contains($host, 'ggpht.com');
    }
}
