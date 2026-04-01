<?php

return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => env('STRIPE_CURRENCY', 'usd'),
    'trial_days' => (int) env('BILLING_DEFAULT_TRIAL_DAYS', 30),
    'annual_discount_percent' => (int) env('BILLING_ANNUAL_DISCOUNT_PERCENT', 20),

    'plans' => [
        'free' => [
            'name' => 'Basic',
            'access_level' => 'free',
            'description' => 'Free forever for solo recruiters and early testing.',
            'trial_days' => 0,
            'monthly' => [
                'amount' => 0,
                'interval' => 'month',
                'stripe_price_id' => null,
            ],
            'annual' => [
                'amount' => 0,
                'interval' => 'year',
                'stripe_price_id' => null,
                'discount_percent' => 0,
            ],
            'limits' => [
                'job_posts_per_month' => 2,
                'cv_downloads_per_month' => 40,
                'ai_analyses_per_month' => 60,
                'team_members' => 1,
            ],
        ],
        'basic' => [
            'name' => 'Individual',
            'access_level' => 'basic',
            'description' => 'For individual recruiters with monthly hiring needs.',
            'trial_days' => (int) env('BILLING_INDIVIDUAL_MONTHLY_TRIAL_DAYS', 30),
            'monthly' => [
                'amount' => 1900,
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_PRICE_BASIC_MONTHLY', env('STRIPE_PRICE_BASIC')),
            ],
            'annual' => [
                'amount' => 18240, // $19 * 12 * 0.8
                'interval' => 'year',
                'stripe_price_id' => env('STRIPE_PRICE_BASIC_ANNUAL'),
                'discount_percent' => (int) env('BILLING_ANNUAL_DISCOUNT_PERCENT', 20),
            ],
            'limits' => [
                'job_posts_per_month' => 10,
                'cv_downloads_per_month' => 200,
                'ai_analyses_per_month' => 300,
                'team_members' => 3,
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'access_level' => 'pro',
            'description' => 'Best for growing teams with steady hiring needs.',
            'monthly' => [
                'amount' => 4900,
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_PRICE_PRO_MONTHLY', env('STRIPE_PRICE_PRO')),
            ],
            'annual' => [
                'amount' => 47040, // $49 * 12 * 0.8
                'interval' => 'year',
                'stripe_price_id' => env('STRIPE_PRICE_PRO_ANNUAL'),
                'discount_percent' => (int) env('BILLING_ANNUAL_DISCOUNT_PERCENT', 20),
            ],
            'limits' => [
                'job_posts_per_month' => 50,
                'cv_downloads_per_month' => 1200,
                'ai_analyses_per_month' => 3000,
                'team_members' => 15,
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'access_level' => 'enterprise',
            'description' => 'Advanced AI recruiting workflows for scaling organizations.',
            'monthly' => [
                'amount' => 14900,
                'interval' => 'month',
                'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY', env('STRIPE_PRICE_ENTERPRISE')),
            ],
            'annual' => [
                'amount' => 143040, // $149 * 12 * 0.8
                'interval' => 'year',
                'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL'),
                'discount_percent' => (int) env('BILLING_ANNUAL_DISCOUNT_PERCENT', 20),
            ],
            'limits' => [
                'job_posts_per_month' => -1,
                'cv_downloads_per_month' => -1,
                'ai_analyses_per_month' => -1,
                'team_members' => -1,
            ],
        ],
    ],
];
