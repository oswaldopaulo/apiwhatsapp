<?php

return [
    'messages' => [
        [['tenant_id' => 1, 'message_id' => 1], ['unique' => true]],
        [['tenant_id' => 1, 'session_id' => 1, 'created_at' => -1], []],
        [['tenant_id' => 1, 'status' => 1, 'queued_at' => 1], []],
        [['tenant_id' => 1, 'created_at' => 1, 'status' => 1], []],
        [['tenant_id' => 1, 'session_id' => 1, 'status' => 1, 'created_at' => -1], []],
        [['tenant_id' => 1, 'error_code' => 1, 'created_at' => -1], ['sparse' => true]],
        [['tenant_id' => 1, 'provider_message_id' => 1], ['sparse' => true]],
        [['tenant_id' => 1, 'to' => 1, 'created_at' => -1], []],
    ],

    'message_logs' => [
        [['tenant_id' => 1, 'message_id' => 1, 'created_at' => -1], []],
        [['tenant_id' => 1, 'level' => 1, 'created_at' => -1], []],
    ],

    'webhook_events' => [
        [['tenant_id' => 1, 'event_id' => 1], ['unique' => true, 'sparse' => true]],
        [['tenant_id' => 1, 'status' => 1, 'received_at' => -1], []],
        [['tenant_id' => 1, 'provider' => 1, 'received_at' => -1], []],
    ],

    'session_events' => [
        [['tenant_id' => 1, 'session_id' => 1, 'occurred_at' => -1], []],
        [['tenant_id' => 1, 'event_type' => 1, 'occurred_at' => -1], []],
    ],

    'queue_events' => [
        [['tenant_id' => 1, 'job_id' => 1, 'occurred_at' => -1], []],
        [['tenant_id' => 1, 'queue' => 1, 'event_type' => 1, 'occurred_at' => -1], []],
    ],

    'outgoing_webhook_logs' => [
        [['tenant_id' => 1, 'webhook_id' => 1, 'created_at' => -1], []],
        [['tenant_id' => 1, 'status' => 1, 'next_retry_at' => 1], []],
        [['tenant_id' => 1, 'event_type' => 1, 'created_at' => -1], []],
    ],

    'audit_logs' => [
        [['tenant_id' => 1, 'occurred_at' => -1], []],
        [['tenant_id' => 1, 'action' => 1, 'occurred_at' => -1], []],
        [['tenant_id' => 1, 'user_id' => 1, 'occurred_at' => -1], []],
        [['occurred_at' => 1], ['expireAfterSeconds' => 15552000]],
    ],
];
