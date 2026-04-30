<?php

return [

    'tokens' => [
        'access_token_minutes' => (int) env('PASSPORT_ACCESS_TOKEN_MINUTES', 15),
        'refresh_token_days' => (int) env('PASSPORT_REFRESH_TOKEN_DAYS', 30),
        'personal_access_token_days' => (int) env('PASSPORT_PERSONAL_ACCESS_TOKEN_DAYS', 7),
    ],

    'scopes' => [
        'messages:send' => 'Send WhatsApp messages.',
        'messages:read' => 'Read WhatsApp messages.',
        'sessions:manage' => 'Manage WhatsApp sessions.',
        'stats:read' => 'Read WhatsApp statistics.',
        'webhooks:manage' => 'Manage webhook endpoints.',
        'config:read' => 'Read tenant configuration.',
        'config:write' => 'Update tenant configuration.',
    ],

];
