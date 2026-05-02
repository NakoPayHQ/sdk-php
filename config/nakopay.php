<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NakoPay API Key
    |--------------------------------------------------------------------------
    |
    | Your secret API key (sk_live_* or sk_test_*). Never use publishable
    | keys (pk_*) in server-side code.
    |
    */
    'api_key' => env('NAKOPAY_SECRET_KEY', env('NAKOPAY_API_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | Override only if you are testing against a fork or self-hosted instance.
    |
    */
    'base_url' => env('NAKOPAY_BASE_URL', 'https://api.nakopay.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    */
    'api_version' => env('NAKOPAY_API_VERSION', '2025-04-20'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (float) env('NAKOPAY_TIMEOUT', 30.0),

    /*
    |--------------------------------------------------------------------------
    | Max Retries
    |--------------------------------------------------------------------------
    |
    | Number of automatic retries for 429 and 5xx errors.
    |
    */
    'max_retries' => (int) env('NAKOPAY_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Used for verifying webhook signatures (whsec_*).
    |
    */
    'webhook_secret' => env('NAKOPAY_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Webhook Queue
    |--------------------------------------------------------------------------
    |
    | When set, incoming webhooks are dispatched to this queue for
    | asynchronous processing instead of being handled inline.
    | Set to null or false to process inline.
    |
    */
    'webhook_queue' => env('NAKOPAY_WEBHOOK_QUEUE', null),
];
