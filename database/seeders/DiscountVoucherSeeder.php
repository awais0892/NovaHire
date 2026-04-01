<?php

namespace Database\Seeders;

use App\Models\DiscountVoucher;
use Illuminate\Database\Seeder;

class DiscountVoucherSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'WELCOME5',
                'type' => 'percent',
                'value' => 5,
                'min_subtotal' => 1000,
                'applies_to_plans' => ['basic', 'pro', 'enterprise'],
                'billing_cycles' => ['monthly', 'annual'],
                'first_purchase_only' => true,
                'max_redemptions' => 2000,
                'per_company_limit' => 1,
                'description' => '5% off on first paid checkout.',
            ],
            [
                'code' => 'GROWTH10',
                'type' => 'percent',
                'value' => 10,
                'min_subtotal' => 3000,
                'applies_to_plans' => ['pro', 'enterprise'],
                'billing_cycles' => ['monthly', 'annual'],
                'first_purchase_only' => false,
                'max_redemptions' => 1500,
                'per_company_limit' => 2,
                'description' => '10% off for growth and scale teams.',
            ],
            [
                'code' => 'SCALE15',
                'type' => 'percent',
                'value' => 15,
                'min_subtotal' => 10000,
                'max_discount' => 5000,
                'applies_to_plans' => ['enterprise'],
                'billing_cycles' => ['annual'],
                'first_purchase_only' => false,
                'max_redemptions' => 500,
                'per_company_limit' => 1,
                'description' => '15% off enterprise annual checkout.',
            ],
            [
                'code' => 'ANNUAL20',
                'type' => 'percent',
                'value' => 20,
                'min_subtotal' => 1000,
                'applies_to_plans' => ['basic', 'pro', 'enterprise'],
                'billing_cycles' => ['annual'],
                'first_purchase_only' => false,
                'max_redemptions' => 3000,
                'per_company_limit' => 1,
                'description' => 'Extra annual-only campaign discount.',
            ],
            [
                'code' => 'BASIC50',
                'type' => 'fixed',
                'value' => 50,
                'currency' => 'usd',
                'min_subtotal' => 1000,
                'applies_to_plans' => ['basic'],
                'billing_cycles' => ['annual'],
                'first_purchase_only' => false,
                'max_redemptions' => 300,
                'per_company_limit' => 1,
                'description' => '$50 off Basic annual.',
            ],
        ];

        foreach ($rows as $row) {
            DiscountVoucher::query()->updateOrCreate(
                ['code' => $row['code']],
                array_merge([
                    'currency' => strtolower((string) config('stripe.currency', 'usd')),
                    'is_active' => true,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addYear(),
                ], $row)
            );
        }
    }
}

