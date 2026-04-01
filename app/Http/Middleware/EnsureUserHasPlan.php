<?php

namespace App\Http\Middleware;

use App\Services\Billing\StripeBillingService;
use Closure;
use Illuminate\Http\Request;

class EnsureUserHasPlan
{
    public function __construct(private readonly StripeBillingService $billing)
    {
    }

    public function handle(Request $request, Closure $next, string $requiredPlan)
    {
        $company = auth()->user()?->company;

        if (!$this->billing->companyMeetsPlan($company, $requiredPlan)) {
            return redirect()
                ->route('account.settings')
                ->withErrors(['billing' => "The {$requiredPlan} plan is required to access this feature."]);
        }

        return $next($request);
    }
}
