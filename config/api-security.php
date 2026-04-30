<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution
    |--------------------------------------------------------------------------
    */

    'tenant' => [
        'header' => env('API_TENANT_HEADER', 'X-Tenant-ID'),
        'required' => (bool) env('API_TENANT_REQUIRED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'api_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),
        'auth_per_minute' => (int) env('API_AUTH_RATE_LIMIT_PER_MINUTE', 20),
        'webhook_per_minute' => (int) env('API_WEBHOOK_RATE_LIMIT_PER_MINUTE', 300),
        'store' => env('API_RATE_LIMIT_STORE', env('CACHE_STORE', 'database')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Signatures
    |--------------------------------------------------------------------------
    */

    'webhooks' => [
        'secret' => env('WHATSAPP_WEBHOOK_SECRET'),
        'signature_header' => env('WHATSAPP_WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
        'timestamp_header' => env('WHATSAPP_WEBHOOK_TIMESTAMP_HEADER', 'X-Webhook-Timestamp'),
        'tolerance_seconds' => (int) env('WHATSAPP_WEBHOOK_TOLERANCE_SECONDS', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Headers
    |--------------------------------------------------------------------------
    */

    'headers' => [
        'powered_by' => false,
        'content_type_options' => 'nosniff',
        'frame_options' => 'DENY',
        'referrer_policy' => 'no-referrer',
    ],

];
