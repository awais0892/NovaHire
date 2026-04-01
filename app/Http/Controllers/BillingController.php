<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Billing\StripeBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(private readonly StripeBillingService $billing)
    {
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::in($this->billing->availablePlanKeys())],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'annual'])],
            'voucher_code' => ['nullable', 'string', 'max:64'],
        ]);

        $company = $this->currentCompanyOrFail();
        $planKey = (string) $validated['plan'];
        $billingCycle = (string) ($validated['billing_cycle'] ?? 'monthly');
        $voucherCode = isset($validated['voucher_code']) ? (string) $validated['voucher_code'] : null;

        if ($planKey === 'free') {
            $this->billing->switchToFreePlan($company);
            return redirect()->route('account.settings')->with('success', 'Basic free plan activated.');
        }

        $session = $this->billing->createCheckoutSession(
            $company,
            $planKey,
            $billingCycle,
            $voucherCode,
            auth()->id()
        );

        return redirect()->away((string) $session->url);
    }

    public function success(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return redirect()->route('account.settings')->withErrors(['billing' => 'Invalid billing session.']);
        }

        $company = $this->billing->processCheckoutSuccess($sessionId);
        if (!$company) {
            Log::warning('Stripe success callback failed to map checkout session.', [
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('account.settings')->withErrors(['billing' => 'Unable to verify payment confirmation.']);
        }

        return redirect()->route('account.settings')->with('success', 'Subscription activated successfully.');
    }

    public function cancel(): RedirectResponse
    {
        return redirect()->route('account.settings')->withErrors(['billing' => 'Checkout was cancelled.']);
    }

    public function portal(): RedirectResponse
    {
        $company = $this->currentCompanyOrFail();
        $portalUrl = $this->billing->createBillingPortalSession($company);

        return redirect()->away($portalUrl);
    }

    private function currentCompanyOrFail(): Company
    {
        $company = auth()->user()?->company;
        abort_unless($company, 404, 'No company is linked to this user.');

        return $company;
    }
}
