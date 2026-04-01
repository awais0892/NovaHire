<?php

return [
    'uk_timezone' => env('UK_TIMEZONE', 'Europe/London'),

    'uk_bank_holidays' => [
        'url' => env('UK_BANK_HOLIDAYS_URL', 'https://www.gov.uk/bank-holidays.json'),
        'division' => env('UK_BANK_HOLIDAYS_DIVISION', 'england-and-wales'),
        'cache_hours' => (int) env('UK_BANK_HOLIDAYS_CACHE_HOURS', 168),
        'timeout_seconds' => (int) env('UK_BANK_HOLIDAYS_TIMEOUT', 8),
    ],

    'realtime_notifications' => [
        'broadcast_enabled' => filter_var(
            env('REALTIME_NOTIFICATIONS_ENABLED', env('VITE_REALTIME_ENABLED', false)),
            FILTER_VALIDATE_BOOL
        ),
    ],

    'phase2' => [
        'reject_max_score' => (int) env('PHASE2_REJECT_MAX_SCORE', 50),
        'shortlist_max_score' => (int) env('PHASE2_SHORTLIST_MAX_SCORE', 70),
        'final_statuses' => ['offer', 'hired'],
    ],

    'phase3' => [
        'retry_backoff_seconds' => [60, 180, 540],
        'digest_send_time' => env('PHASE3_DIGEST_SEND_TIME', '07:30'),
        'dedupe_minutes' => (int) env('PHASE3_EMAIL_DEDUPE_MINUTES', 30),
    ],

    'phase4' => [
        'default_timezone' => env('PHASE4_DEFAULT_TIMEZONE', env('UK_TIMEZONE', 'Europe/London')),
        'default_slot_duration_minutes' => (int) env('PHASE4_SLOT_DURATION_MINUTES', 45),
        'default_buffer_minutes' => (int) env('PHASE4_BUFFER_MINUTES', 10),
        'weekend_enabled' => (bool) env('PHASE4_WEEKEND_ENABLED', false),
        'auto_generate_days' => (int) env('PHASE4_AUTO_GENERATE_DAYS', 28),
        'default_mode' => env('PHASE4_DEFAULT_MODE', 'video'),
    ],
];
