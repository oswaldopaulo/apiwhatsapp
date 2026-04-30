<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Queue
    |--------------------------------------------------------------------------
    |
    | Every outbound message must be queued. Use "default" to inherit the
    | application queue connection, or explicitly choose "redis" or "database".
    |
    */

    'queue' => [
        'driver' => env('WHATSAPP_QUEUE_DRIVER', 'default'),
        'connection' => env('WHATSAPP_QUEUE_CONNECTION'),
        'name' => env('WHATSAPP_QUEUE_NAME', 'messages'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Timing
    |--------------------------------------------------------------------------
    |
    | Conservative defaults reduce burst traffic and leave room for tenant-level
    | controls. Values are expressed in seconds unless explicitly documented.
    |
    */

    'delivery' => [
        'default_delay_min' => (int) env('WHATSAPP_DEFAULT_DELAY_MIN', 3),
        'default_delay_max' => (int) env('WHATSAPP_DEFAULT_DELAY_MAX', 12),
        'max_messages_per_minute' => (int) env('WHATSAPP_MAX_MESSAGES_PER_MINUTE', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anti-ban Controls
    |--------------------------------------------------------------------------
    */

    'anti_ban' => [
        'enabled' => (bool) env('WHATSAPP_ANTI_BAN_ENABLED', true),
        'redis_connection' => env('WHATSAPP_ANTI_BAN_REDIS_CONNECTION', 'default'),
        'key_prefix' => env('WHATSAPP_ANTI_BAN_KEY_PREFIX', 'whatsapp:anti-ban'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
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
    | Persistence
    |--------------------------------------------------------------------------
    */

    'mongodb' => [
        'connection' => env('WHATSAPP_MONGODB_CONNECTION', 'mongodb'),
        'messages_collection' => env('WHATSAPP_MESSAGES_COLLECTION', 'messages'),
        'events_collection' => env('WHATSAPP_EVENTS_COLLECTION', 'event_logs'),
        'webhooks_collection' => env('WHATSAPP_WEBHOOKS_COLLECTION', 'webhook_deliveries'),
        'statistics_collection' => env('WHATSAPP_STATISTICS_COLLECTION', 'statistics'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Realtime
    |--------------------------------------------------------------------------
    */

    'realtime' => [
        'broadcast_connection' => env('BROADCAST_CONNECTION', 'log'),
        'presence_channel_prefix' => env('WHATSAPP_PRESENCE_CHANNEL_PREFIX', 'tenant'),
    ],

];
