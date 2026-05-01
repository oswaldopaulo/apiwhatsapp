<?php

return [

    'enabled' => env('ACTIVITY_LOGGER_ENABLED', env('AUDIT_ACTIVITY_LOG_ENABLED', true)),

    'delete_records_older_than_days' => env('AUDIT_LOG_RETENTION_DAYS', 180),

    'default_log_name' => env('AUDIT_ACTIVITY_LOG_NAME', 'audit'),

    'activity_model' => env('ACTIVITY_LOGGER_MODEL', 'Spatie\Activitylog\Models\Activity'),

    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),

    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),

];
