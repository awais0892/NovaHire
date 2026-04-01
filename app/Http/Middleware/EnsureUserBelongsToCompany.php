<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserBelongsToCompany
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Candidates are public-side users and may not belong to a company yet.
        if ($user && $user->hasRole('candidate')) {
            return $next($request);
        }

        if ($user && ! $user->hasRole('super_admin')) {
            if (! $user->company_id || optional($user->company)->status === 'suspended') {
                auth()->logout();
                return redirect()->route('login')
                    ->withErrors(['account' => 'Your account has been suspended.']);
            }
        }

        return $next($request);
    }
}
