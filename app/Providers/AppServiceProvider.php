<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/livewire/livewire.js', function () use ($handle) {
                if (is_array($handle) && isset($handle[0], $handle[1]) && is_string($handle[0])) {
                    $instance = app($handle[0]);
                    $response = $instance->{$handle[1]}();
                } else {
                    $response = $handle();
                }
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');

                return $response;
            });
        });

        RateLimiter::for('checkout-session', function (Request $request) {
            $userId = auth()->id() ?: $request->ip();

            return [
                Limit::perMinute(5)->by((string) $userId),
                Limit::perHour(20)->by((string) $userId),
            ];
        });

        RateLimiter::for('auth-login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));
            $key = $email !== '' ? $email . '|' . $request->ip() : $request->ip();

            return [
                Limit::perMinute(6)->by($key),
                Limit::perHour(30)->by((string) $request->ip()),
            ];
        });

        RateLimiter::for('auth-register', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));
            $key = $email !== '' ? $email . '|' . $request->ip() : $request->ip();

            return [
                Limit::perMinute(4)->by($key),
                Limit::perHour(12)->by((string) $request->ip()),
            ];
        });

        RateLimiter::for('auth-register-resend', function (Request $request) {
            $email = strtolower(trim((string) $request->input(
                'email',
                (string) $request->session()->get('auth.registration_verification_email', '')
            )));
            $key = $email !== '' ? $email . '|' . $request->ip() : $request->ip();

            return [
                Limit::perMinute(3)->by($key),
                Limit::perHour(10)->by((string) $request->ip()),
            ];
        });
    }
}
