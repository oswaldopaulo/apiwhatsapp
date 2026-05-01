<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Enums\QueueEventType;
use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class QueueEvent extends MongoModel
{
    use BelongsToTenantDocument;

    protected string $collection = 'queue_events';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => QueueEventType::class,
            'payload' => 'array',
            'metadata' => 'array',
            'attempt' => 'integer',
            'delay_seconds' => 'integer',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
