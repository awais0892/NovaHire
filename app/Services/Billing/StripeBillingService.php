<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\DiscountVoucher;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;

class StripeBillingService
{
    public function __construct(private readonly VoucherService $vouchers)
    {
    }

    public function ensureConfigured(): void
    {
        if (!$this->hasStripeKeys()) {
            abort(422, 'Stripe credentials are not configured.');
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));
    }

    public function getPlan(string $planKey): ?array
    {
        return config("stripe.plans.{$planKey}");
    }

    public function availablePlanKeys(bool $includeFree = true): array
    {
        $keys = array_keys((array) config('stripe.plans', []));
        if ($includeFree) {
            return $keys;
        }

        return array_values(array_filter($keys, fn($key) => $key !== 'free'));
    }

    public function createCheckoutSession(
        Company $company,
        string $planKey,
        string $billingCycle = 'monthly',
        ?string $voucherCode = null,
        ?int $actorUserId = null
    ): CheckoutSession {
        $this->ensureConfigured();

        $planPricing = $this->resolvePlanPricing($planKey, $billingCycle);
        if (!$planPricing) {
            Log::warning('Stripe checkout blocked due to invalid plan key.', [
                'plan' => $planKey,
                'billing_cycle' => $billingCycle,
                'company_id' => $company->id,
                'user_id' => $actorUserId,
            ]);
            abort(422, 'Invalid plan selected.');
        }

        if ($planKey === 'free') {
            abort(422, 'Free plan does not require checkout.');
        }

        $company = $this->ensureCustomer($company);
        $successUrl = $this->routeUrl('billing.success', ['session_id' => '{CHECKOUT_SESSION_ID}']);
        $cancelUrl = $this->routeUrl('billing.cancel');
        $idempotencyKey = sprintf(
            'checkout:%d:%s:%s:%s',
            $company->id,
            $planKey,
            $billingCycle,
            now()->format('YmdHi')
        );

        $subtotalCents = (int) ($planPricing['amount'] ?? 0);
        $voucherPayload = $this->vouchers->findApplicable($voucherCode, $company, $planKey, $billingCycle, $subtotalCents);
        $discountCents = (int) ($voucherPayload['discount_cents'] ?? 0);
        $finalSubtotalCents = (int) ($voucherPayload['final_subtotal_cents'] ?? $subtotalCents);

        $lineItem = $this->buildLineItem($planPricing, $planKey, $billingCycle, $finalSubtotalCents);
        $trialDays = $this->resolveTrialDays($planKey, $billingCycle);

        $subscriptionData = [
            'metadata' => [
                'company_id' => (string) $company->id,
                'plan_key' => $planKey,
                'billing_cycle' => $billingCycle,
            ],
        ];
        if ($trialDays > 0) {
            $subscriptionData['trial_period_days'] = $trialDays;
        }

        return CheckoutSession::create([
            'mode' => 'subscription',
            'customer' => $company->stripe_customer_id,
            'client_reference_id' => (string) $company->id,
            'metadata' => [
                'company_id' => (string) $company->id,
                'plan_key' => $planKey,
                'billing_cycle' => $billingCycle,
                'voucher_code' => strtoupper(trim((string) $voucherCode)),
                'voucher_discount_cents' => (string) $discountCents,
                'voucher_subtotal_cents' => (string) $subtotalCents,
                'trial_days' => (string) $trialDays,
                'actor_user_id' => (string) ($actorUserId ?? 0),
            ],
            'subscription_data' => $subscriptionData,
            'line_items' => [$lineItem],
            'allow_promotion_codes' => true,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ], ['idempotency_key' => $idempotencyKey]);
    }

    public function processCheckoutSuccess(string $sessionId): ?Company
    {
        $this->ensureConfigured();

        $session = CheckoutSession::retrieve($sessionId);
        if (($session->mode ?? null) !== 'subscription') {
            return null;
        }

        $companyId = (int) ($session->client_reference_id ?: data_get($session, 'metadata.company_id'));
        $planKey = (string) (data_get($session, 'metadata.plan_key') ?? 'basic');
        $billingCycle = (string) (data_get($session, 'metadata.billing_cycle') ?? 'monthly');
        $subscriptionId = (string) ($session->subscription ?? '');

        $company = Company::find($companyId);
        if (!$company || $subscriptionId === '') {
            return null;
        }

        $subscription = Subscription::retrieve($subscriptionId);
        $this->applySubscriptionToCompany($company, $subscription, $planKey, $billingCycle);

        $voucherCode = strtoupper(trim((string) (data_get($session, 'metadata.voucher_code') ?? '')));
        $voucherDiscount = (int) (data_get($session, 'metadata.voucher_discount_cents') ?? 0);
        $voucherSubtotal = (int) (data_get($session, 'metadata.voucher_subtotal_cents') ?? 0);
        $actorUserId = (int) (data_get($session, 'metadata.actor_user_id') ?? 0);

        $this->recordVoucherRedemptionIfNeeded(
            $voucherCode,
            $company,
            $actorUserId > 0 ? $actorUserId : null,
            $sessionId,
            $voucherSubtotal,
            $voucherDiscount
        );

        return $company->refresh();
    }

    public function createBillingPortalSession(Company $company): string
    {
        $this->ensureConfigured();
        $company = $this->ensureCustomer($company);

        $portal = \Stripe\BillingPortal\Session::create([
            'customer' => $company->stripe_customer_id,
            'return_url' => $this->routeUrl('account.settings'),
        ]);

        return (string) $portal->url;
    }

    public function handleWebhook(string $payload, string $signatureHeader): array
    {
        $this->ensureConfigured();
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            if ($secret !== '') {
                $event = Webhook::constructEvent($payload, $signatureHeader, $secret);
            } else {
                $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
            }
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', ['error' => $e->getMessage()]);
            return ['ok' => false, 'status' => 400];
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook parsing failed.', ['error' => $e->getMessage()]);
            return ['ok' => false, 'status' => 400];
        }

        $eventId = (string) ($event->id ?? '');
        $eventType = (string) ($event->type ?? '');
        $object = $event->data->object ?? null;

        if ($eventId === '' || !$object) {
            return ['ok' => true, 'status' => 200];
        }

        $alreadyProcessed = StripeWebhookEvent::where('stripe_event_id', $eventId)->exists();
        if ($alreadyProcessed) {
            Log::info('Stripe webhook skipped due to idempotency hit.', ['event_id' => $eventId, 'type' => $eventType]);
            return ['ok' => true, 'status' => 200];
        }

        StripeWebhookEvent::create([
            'stripe_event_id' => $eventId,
            'type' => $eventType,
            'payload' => json_decode($payload, true) ?? [],
            'processed_at' => now(),
        ]);

        Log::info('Stripe webhook event received.', ['event_id' => $eventId, 'type' => $eventType]);

        if ($eventType === 'checkout.session.completed') {
            $this->syncFromCheckoutSession($object);
        }

        if ($eventType === 'invoice.paid') {
            $subscriptionId = (string) ($object->subscription ?? '');
            if ($subscriptionId !== '') {
                $this->syncFromSubscriptionId($subscriptionId);
            }
        }

        if ($eventType === 'invoice.payment_failed') {
            $subscriptionId = (string) ($object->subscription ?? '');
            if ($subscriptionId !== '') {
                $this->markSubscriptionAsPastDue($subscriptionId);
            }
        }

        if ($eventType === 'customer.subscription.deleted') {
            $subscriptionId = (string) ($object->id ?? '');
            if ($subscriptionId !== '') {
                $this->markSubscriptionAsCanceled($subscriptionId);
            }
        }

        return ['ok' => true, 'status' => 200];
    }

    public function companyHasActiveSubscription(?Company $company): bool
    {
        if (!$company) {
            return false;
        }

        $status = strtolower((string) ($company->billing_status ?? ''));
        $activeStatus = in_array($status, ['active', 'trialing'], true);
        $validPeriod = !$company->billing_period_ends_at || $company->billing_period_ends_at->isFuture();
        $plan = strtolower((string) $company->plan);

        if ($activeStatus && $validPeriod && in_array($plan, ['basic', 'pro', 'enterprise'], true)) {
            return true;
        }

        return false;
    }

    public function companyMeetsPlan(?Company $company, string $requiredPlan): bool
    {
        if (!$this->companyHasActiveSubscription($company)) {
            return false;
        }

        $order = ['free' => 0, 'basic' => 1, 'pro' => 2, 'enterprise' => 3];
        $currentPlan = strtolower((string) ($company->plan ?? 'free'));
        $requiredPlan = strtolower($requiredPlan);

        return ($order[$currentPlan] ?? 0) >= ($order[$requiredPlan] ?? PHP_INT_MAX);
    }

    public function switchToFreePlan(Company $company): Company
    {
        $company->update([
            'plan' => 'free',
            'status' => 'active',
            'billing_status' => 'inactive',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => null,
            'stripe_subscription_id' => null,
            'stripe_price_id' => null,
            'billing_period_ends_at' => null,
        ]);

        return $company->refresh();
    }

    private function ensureCustomer(Company $company): Company
    {
        if ($company->stripe_customer_id) {
            return $company;
        }

        $customer = Customer::create([
            'name' => $company->name,
            'email' => $company->email,
            'metadata' => [
                'company_id' => (string) $company->id,
            ],
        ]);

        $company->update(['stripe_customer_id' => (string) $customer->id]);

        return $company->fresh();
    }

    private function buildLineItem(array $plan, string $planKey, string $billingCycle, int $amountCents): array
    {
        $stripePriceId = (string) ($plan['stripe_price_id'] ?? '');
        if ($stripePriceId !== '' && $amountCents === (int) ($plan['amount'] ?? 0)) {
            return [
                'price' => $stripePriceId,
                'quantity' => 1,
            ];
        }

        return [
            'quantity' => 1,
            'price_data' => [
                'currency' => (string) config('stripe.currency', 'usd'),
                'unit_amount' => $amountCents,
                'recurring' => [
                    'interval' => (string) ($plan['interval'] ?? ($billingCycle === 'annual' ? 'year' : 'month')),
                ],
                'product_data' => [
                    'name' => 'NovaHire ' . (string) ($plan['name'] ?? ucfirst($planKey)),
                    'description' => (string) ($plan['description'] ?? ''),
                ],
            ],
        ];
    }

    private function syncFromCheckoutSession(object $session): void
    {
        $companyId = (int) ($session->client_reference_id ?? data_get($session, 'metadata.company_id', 0));
        $planKey = (string) (data_get($session, 'metadata.plan_key') ?? 'basic');
        $billingCycle = (string) (data_get($session, 'metadata.billing_cycle') ?? 'monthly');
        $subscriptionId = (string) ($session->subscription ?? '');

        if ($companyId < 1 || $subscriptionId === '') {
            return;
        }

        $company = Company::find($companyId);
        if (!$company) {
            Log::warning('Stripe checkout session references missing company.', ['company_id' => $companyId]);
            return;
        }

        $subscription = Subscription::retrieve($subscriptionId);
        $this->applySubscriptionToCompany($company, $subscription, $planKey, $billingCycle);

        $voucherCode = strtoupper(trim((string) (data_get($session, 'metadata.voucher_code') ?? '')));
        $voucherDiscount = (int) (data_get($session, 'metadata.voucher_discount_cents') ?? 0);
        $voucherSubtotal = (int) (data_get($session, 'metadata.voucher_subtotal_cents') ?? 0);
        $actorUserId = (int) (data_get($session, 'metadata.actor_user_id') ?? 0);

        $this->recordVoucherRedemptionIfNeeded(
            $voucherCode,
            $company,
            $actorUserId > 0 ? $actorUserId : null,
            (string) ($session->id ?? ''),
            $voucherSubtotal,
            $voucherDiscount
        );
    }

    private function syncFromSubscriptionId(string $subscriptionId): void
    {
        $company = Company::where('stripe_subscription_id', $subscriptionId)->first();
        if (!$company) {
            return;
        }

        $subscription = Subscription::retrieve($subscriptionId);
        $planKey = (string) (data_get($subscription, 'metadata.plan_key') ?: $company->plan ?: 'basic');
        $billingCycle = (string) (data_get($subscription, 'metadata.billing_cycle') ?: ($company->billing_cycle ?: 'monthly'));
        $this->applySubscriptionToCompany($company, $subscription, $planKey, $billingCycle);
    }

    private function markSubscriptionAsPastDue(string $subscriptionId): void
    {
        $company = Company::where('stripe_subscription_id', $subscriptionId)->first();
        if (!$company) {
            return;
        }

        $company->update([
            'billing_status' => 'past_due',
            'status' => 'active',
        ]);
    }

    private function markSubscriptionAsCanceled(string $subscriptionId): void
    {
        $company = Company::where('stripe_subscription_id', $subscriptionId)->first();
        if (!$company) {
            return;
        }

        $company->update([
            'plan' => 'free',
            'billing_status' => 'canceled',
            'billing_cycle' => 'monthly',
            'billing_period_ends_at' => now(),
            'stripe_subscription_id' => null,
            'stripe_price_id' => null,
        ]);
    }

    private function applySubscriptionToCompany(
        Company $company,
        Subscription $subscription,
        string $planKey,
        string $billingCycle = 'monthly'
    ): void {
        $currentPeriodEnd = isset($subscription->current_period_end)
            ? now()->setTimestamp((int) $subscription->current_period_end)
            : null;

        $priceId = data_get($subscription, 'items.data.0.price.id');
        $safePlan = in_array($planKey, ['basic', 'pro', 'enterprise'], true) ? $planKey : 'basic';

        $company->update([
            'plan' => $safePlan,
            'status' => 'active',
            'stripe_customer_id' => (string) ($subscription->customer ?? $company->stripe_customer_id),
            'stripe_subscription_id' => (string) $subscription->id,
            'stripe_price_id' => $priceId ? (string) $priceId : null,
            'billing_status' => (string) ($subscription->status ?? 'active'),
            'billing_cycle' => $billingCycle,
            'billing_period_ends_at' => $currentPeriodEnd,
        ]);
    }

    private function resolvePlanPricing(string $planKey, string $billingCycle): ?array
    {
        $plan = $this->getPlan($planKey);
        if (!$plan) {
            return null;
        }

        $cycleKey = strtolower($billingCycle) === 'annual' ? 'annual' : 'monthly';
        $cycle = (array) data_get($plan, $cycleKey, []);
        if ($cycle === []) {
            $cycle = [
                'amount' => (int) data_get($plan, 'amount', 0),
                'interval' => (string) data_get($plan, 'interval', $cycleKey === 'annual' ? 'year' : 'month'),
                'stripe_price_id' => data_get($plan, 'stripe_price_id'),
            ];
        }

        return array_merge($plan, $cycle);
    }

    private function resolveTrialDays(string $planKey, string $billingCycle): int
    {
        if (strtolower($billingCycle) !== 'monthly') {
            return 0;
        }

        $configured = (int) data_get(config("stripe.plans.{$planKey}"), 'trial_days', 0);
        return max(0, $configured);
    }

    private function recordVoucherRedemptionIfNeeded(
        string $voucherCode,
        Company $company,
        ?int $userId,
        string $checkoutSessionId,
        int $subtotalCents,
        int $discountCents
    ): void {
        if ($voucherCode === '' || $discountCents < 1 || $checkoutSessionId === '') {
            return;
        }

        $voucher = DiscountVoucher::query()->where('code', $voucherCode)->first();
        if (!$voucher) {
            return;
        }

        $already = $voucher->redemptions()
            ->where('checkout_session_id', $checkoutSessionId)
            ->exists();

        if ($already) {
            return;
        }

        $this->vouchers->redeem(
            $voucher,
            $company,
            $userId,
            $checkoutSessionId,
            $subtotalCents,
            $discountCents,
            (string) config('stripe.currency', 'usd'),
            ['source' => 'checkout_success']
        );
    }

    private function routeUrl(string $routeName, array $params = []): string
    {
        $url = route($routeName, $params);

        if (!app()->isLocal() && str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }

        return $url;
    }

    private function hasStripeKeys(): bool
    {
        return filled(config('services.stripe.key')) && filled(config('services.stripe.secret'));
    }
}
