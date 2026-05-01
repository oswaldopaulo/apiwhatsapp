<?php

return [

    'enabled' => env('AUDIT_LOG_ENABLED', true),

    'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 180),

    'mongo_collection' => env('AUDIT_LOG_MONGODB_COLLECTION', 'audit_logs'),

    'activity_log' => [
        'enabled' => env('AUDIT_ACTIVITY_LOG_ENABLED', true),
        'log_name' => env('AUDIT_ACTIVITY_LOG_NAME', 'audit'),
    ],

    'metadata' => [
        'max_string_length' => (int) env('AUDIT_METADATA_MAX_STRING_LENGTH', 500),
    ],

];
