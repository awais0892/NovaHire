<?php

namespace App\Http\Middleware;

use App\Services\Billing\StripeBillingService;
use Closure;
use Illuminate\Http\Request;

class EnsureUserHasActiveSubscription
{
    public function __construct(private readonly StripeBillingService $billing)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $company = auth()->user()?->company;

        if (!$this->billing->companyHasActiveSubscription($company)) {
            return redirect()
                ->route('account.settings')
                ->withErrors(['billing' => 'Your subscription is inactive. Please upgrade or retry payment.']);
        }

        return $next($request);
    }
}
