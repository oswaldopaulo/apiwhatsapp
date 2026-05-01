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
        'ip_per_minute' => (int) env('API_IP_RATE_LIMIT_PER_MINUTE', env('API_RATE_LIMIT_PER_MINUTE', 120)),
        'tenant_per_minute' => (int) env('API_TENANT_RATE_LIMIT_PER_MINUTE', 600),
        'sensitive_per_minute' => (int) env('API_SENSITIVE_RATE_LIMIT_PER_MINUTE', 60),
        'session_per_minute' => (int) env('API_SESSION_RATE_LIMIT_PER_MINUTE', 30),
        'auth_per_minute' => (int) env('API_AUTH_RATE_LIMIT_PER_MINUTE', 20),
        'webhook_per_minute' => (int) env('API_WEBHOOK_RATE_LIMIT_PER_MINUTE', 300),
        'decay_seconds' => (int) env('API_RATE_LIMIT_DECAY_SECONDS', 60),
        'abuse_revocation_threshold' => (int) env('API_ABUSE_REVOCATION_THRESHOLD', 3),
        'store' => env('API_RATE_LIMIT_STORE', env('CACHE_STORE', 'database')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Locks
    |--------------------------------------------------------------------------
    */

    'locks' => [
        'enabled' => (bool) env('API_SECURITY_LOCKS_ENABLED', true),
        'store' => env('API_SECURITY_LOCK_STORE', env('CACHE_STORE', 'database')),
        'ttl_seconds' => (int) env('API_SECURITY_LOCK_TTL_SECONDS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON API Enforcement
    |--------------------------------------------------------------------------
    */

    'json' => [
        'require_accept' => (bool) env('API_REQUIRE_JSON_ACCEPT', true),
        'require_content_type' => (bool) env('API_REQUIRE_JSON_CONTENT_TYPE', true),
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
        'xss_protection' => '0',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=()',
        'content_security_policy' => "default-src 'none'; frame-ancestors 'none'; base-uri 'none'",
        'hsts' => 'max-age=31536000; includeSubDomains',
    ],

];
