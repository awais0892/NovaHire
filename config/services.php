<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'country' => env('GOOGLE_MAPS_COUNTRY'),
    ],

    'google_calendar' => [
        'enabled' => filter_var(env('GOOGLE_CALENDAR_ENABLED', false), FILTER_VALIDATE_BOOL),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
        'refresh_token' => env('GOOGLE_CALENDAR_REFRESH_TOKEN'),
        'timeout_seconds' => (int) env('GOOGLE_CALENDAR_TIMEOUT', 10),
    ],

    'maps' => [
        'provider' => env('MAPS_PROVIDER', 'geoapify'),
        'timeout' => (int) env('MAPS_HTTP_TIMEOUT', 5),
        'retry_times' => (int) env('MAPS_HTTP_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('MAPS_HTTP_RETRY_SLEEP_MS', 200),
        'locale' => env('MAPS_LOCALE', 'en'),
        'log_failures' => filter_var(env('MAPS_LOG_FAILURES', true), FILTER_VALIDATE_BOOL),
    ],

    'geoapify' => [
        'key' => env('GEOAPIFY_API_KEY'),
        'country' => env('GEOAPIFY_COUNTRY'),
        'base_url' => env('GEOAPIFY_BASE_URL', 'https://api.geoapify.com'),
        'maps_base_url' => env('GEOAPIFY_MAPS_BASE_URL', 'https://maps.geoapify.com'),
        'autocomplete_path' => env('GEOAPIFY_AUTOCOMPLETE_PATH', '/v1/geocode/autocomplete'),
        'search_path' => env('GEOAPIFY_SEARCH_PATH', '/v1/geocode/search'),
        'reverse_path' => env('GEOAPIFY_REVERSE_PATH', '/v1/geocode/reverse'),
        'place_details_path' => env('GEOAPIFY_PLACE_DETAILS_PATH', '/v2/place-details'),
        'routing_path' => env('GEOAPIFY_ROUTING_PATH', '/v1/routing'),
        'mapmatching_path' => env('GEOAPIFY_MAPMATCHING_PATH', '/v1/mapmatching'),
        'tile_path' => env('GEOAPIFY_TILE_PATH', '/v1/tile/carto/{z}/{x}/{y}.png'),
        'static_map_path' => env('GEOAPIFY_STATIC_MAP_PATH', '/v1/staticmap'),
        'autocomplete_limit' => (int) env('GEOAPIFY_AUTOCOMPLETE_LIMIT', 8),
    ],

];
