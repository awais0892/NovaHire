<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

class SocialAuthController extends Controller
{
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

        if (empty($googleUser->getEmail())) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google account email is required.',
            ]);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user && ! $user->hasRole('candidate')) {
            return redirect()->route('login')->withErrors([
                'email' => 'This Google sign-in is only for candidate accounts.',
            ]);
        }

        $hasGoogleIdColumn = Schema::hasColumn('users', 'google_id');

        if (! $user) {
            $attributes = [
                'name' => $googleUser->getName() ?: 'Candidate',
                'email' => $googleUser->getEmail(),
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ];
            if ($hasGoogleIdColumn) {
                $attributes['google_id'] = $googleUser->getId();
            }

            $user = User::create($attributes);

            $user->assignRole(Role::findOrCreate('candidate', 'web'));
        } elseif ($hasGoogleIdColumn && empty($user->google_id)) {
            $user->forceFill(['google_id' => $googleUser->getId()])->save();
        }

        Candidate::updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $user->email,
                'name' => $user->name,
                'company_id' => $user->company_id,
            ]
        );

        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user, true);

        return redirect()->route('candidate.profile');
    }
}
