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

    'outgoing_webhooks' => [
        'max_payload_bytes' => (int) env('WHATSAPP_OUTGOING_WEBHOOK_MAX_PAYLOAD_BYTES', 16384),
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistence
    |--------------------------------------------------------------------------
    */

    'mongodb' => [
        'connection' => env('WHATSAPP_MONGODB_CONNECTION', 'mongodb'),
        'messages_collection' => env('WHATSAPP_MESSAGES_COLLECTION', 'messages'),
        'message_logs_collection' => env('WHATSAPP_MESSAGE_LOGS_COLLECTION', 'message_logs'),
        'webhook_events_collection' => env('WHATSAPP_WEBHOOK_EVENTS_COLLECTION', 'webhook_events'),
        'session_events_collection' => env('WHATSAPP_SESSION_EVENTS_COLLECTION', 'session_events'),
        'queue_events_collection' => env('WHATSAPP_QUEUE_EVENTS_COLLECTION', 'queue_events'),
        'outgoing_webhook_logs_collection' => env('WHATSAPP_OUTGOING_WEBHOOK_LOGS_COLLECTION', 'outgoing_webhook_logs'),
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
