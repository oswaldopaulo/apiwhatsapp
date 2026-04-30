<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Backend Selection
    |--------------------------------------------------------------------------
    |
    | The WhatsApp queue can inherit QUEUE_CONNECTION with "default", or force
    | Redis/database through WHATSAPP_QUEUE_DRIVER. Workers are managed by
    | Supervisor in production.
    |
    */

    'whatsapp_driver' => env('WHATSAPP_QUEUE_DRIVER', 'default'),

    'connections' => [
        'primary' => env('QUEUE_CONNECTION', 'database'),
        'redis' => 'redis',
        'fallback' => 'database',
    ],

    /*
    |--------------------------------------------------------------------------
    | Named Queues
    |--------------------------------------------------------------------------
    */

    'queues' => [
        'default' => env('QUEUE_DEFAULT_NAME', 'default'),
        'messages' => env('WHATSAPP_QUEUE_NAME', 'messages'),
        'webhooks' => env('WEBHOOK_QUEUE_NAME', 'webhooks'),
        'events' => env('EVENT_QUEUE_NAME', 'events'),
        'statistics' => env('STATISTICS_QUEUE_NAME', 'statistics'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Worker Defaults
    |--------------------------------------------------------------------------
    */

    'workers' => [
        'tries' => (int) env('QUEUE_WORKER_TRIES', 3),
        'timeout' => (int) env('QUEUE_WORKER_TIMEOUT', 120),
        'backoff' => (int) env('QUEUE_WORKER_BACKOFF', 30),
        'memory' => (int) env('QUEUE_WORKER_MEMORY', 256),
        'sleep' => (int) env('QUEUE_WORKER_SLEEP', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Coordination
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'queue_connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
        'cache_connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
        'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Throttling
    |--------------------------------------------------------------------------
    */

    'throttling' => [
        'enabled' => (bool) env('WHATSAPP_ANTI_BAN_ENABLED', true),
        'max_messages_per_minute' => (int) env('WHATSAPP_MAX_MESSAGES_PER_MINUTE', 20),
        'default_delay_min' => (int) env('WHATSAPP_DEFAULT_DELAY_MIN', 3),
        'default_delay_max' => (int) env('WHATSAPP_DEFAULT_DELAY_MAX', 12),
    ],

];
