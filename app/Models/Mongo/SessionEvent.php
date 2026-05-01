<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Enums\SessionEventType;
use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class SessionEvent extends MongoModel
{
    use BelongsToTenantDocument;

    protected string $collection = 'session_events';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => SessionEventType::class,
            'payload' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
