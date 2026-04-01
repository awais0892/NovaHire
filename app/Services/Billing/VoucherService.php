<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\DiscountVoucher;
use App\Models\VoucherRedemption;

class VoucherService
{
    public function findApplicable(
        ?string $code,
        Company $company,
        string $planKey,
        string $billingCycle,
        int $subtotalCents
    ): ?array {
        $cleanCode = strtoupper(trim((string) $code));
        if ($cleanCode === '') {
            return null;
        }

        $voucher = DiscountVoucher::query()
            ->where('code', $cleanCode)
            ->where('is_active', true)
            ->first();

        if (!$voucher) {
            return null;
        }

        if (!$this->passesRules($voucher, $company, $planKey, $billingCycle, $subtotalCents)) {
            return null;
        }

        $discount = $this->computeDiscountCents($voucher, $subtotalCents);
        if ($discount < 1) {
            return null;
        }

        return [
            'voucher' => $voucher,
            'discount_cents' => $discount,
            'final_subtotal_cents' => max(0, $subtotalCents - $discount),
        ];
    }

    public function redeem(
        DiscountVoucher $voucher,
        Company $company,
        ?int $userId,
        string $checkoutSessionId,
        int $subtotalCents,
        int $discountCents,
        string $currency,
        array $metadata = []
    ): void {
        VoucherRedemption::query()->create([
            'voucher_id' => $voucher->id,
            'company_id' => $company->id,
            'user_id' => $userId,
            'checkout_session_id' => $checkoutSessionId,
            'subtotal' => $subtotalCents,
            'discount_amount' => $discountCents,
            'currency' => strtolower($currency),
            'metadata' => $metadata,
            'redeemed_at' => now(),
        ]);

        $voucher->increment('redeemed_count');
    }

    public function listActiveForPricing(): array
    {
        return DiscountVoucher::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('code')
            ->get()
            ->map(function (DiscountVoucher $voucher): array {
                return [
                    'code' => $voucher->code,
                    'type' => $voucher->type,
                    'value' => (float) $voucher->value,
                    'description' => (string) ($voucher->description ?? ''),
                    'plans' => (array) ($voucher->applies_to_plans ?? []),
                    'billing_cycles' => (array) ($voucher->billing_cycles ?? []),
                    'min_subtotal' => (int) $voucher->min_subtotal,
                    'max_discount' => $voucher->max_discount !== null ? (int) $voucher->max_discount : null,
                    'first_purchase_only' => (bool) $voucher->first_purchase_only,
                ];
            })
            ->all();
    }

    private function passesRules(
        DiscountVoucher $voucher,
        Company $company,
        string $planKey,
        string $billingCycle,
        int $subtotalCents
    ): bool {
        if ($subtotalCents < (int) $voucher->min_subtotal) {
            return false;
        }

        if ($voucher->starts_at && $voucher->starts_at->isFuture()) {
            return false;
        }

        if ($voucher->ends_at && $voucher->ends_at->isPast()) {
            return false;
        }

        if ($voucher->max_redemptions !== null && $voucher->redeemed_count >= $voucher->max_redemptions) {
            return false;
        }

        if (!empty($voucher->applies_to_plans) && !in_array($planKey, (array) $voucher->applies_to_plans, true)) {
            return false;
        }

        if (!empty($voucher->billing_cycles) && !in_array($billingCycle, (array) $voucher->billing_cycles, true)) {
            return false;
        }

        $companyRedemptions = VoucherRedemption::query()
            ->where('voucher_id', $voucher->id)
            ->where('company_id', $company->id)
            ->count();

        if ($voucher->per_company_limit !== null && $companyRedemptions >= $voucher->per_company_limit) {
            return false;
        }

        if ($voucher->first_purchase_only) {
            $hasPaidBefore = filled($company->stripe_subscription_id)
                || VoucherRedemption::query()->where('company_id', $company->id)->exists();
            if ($hasPaidBefore) {
                return false;
            }
        }

        return true;
    }

    private function computeDiscountCents(DiscountVoucher $voucher, int $subtotalCents): int
    {
        $discount = 0;
        if ($voucher->type === 'percent') {
            $discount = (int) round($subtotalCents * ((float) $voucher->value / 100));
        } elseif ($voucher->type === 'fixed') {
            $discount = (int) round(((float) $voucher->value) * 100);
        }

        if ($voucher->max_discount !== null) {
            $discount = min($discount, (int) $voucher->max_discount);
        }

        return max(0, min($discount, $subtotalCents));
    }
}

