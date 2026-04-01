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
    }
}
