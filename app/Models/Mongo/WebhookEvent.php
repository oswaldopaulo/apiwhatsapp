<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Enums\WebhookEventStatus;
use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class WebhookEvent extends MongoModel
{
    use BelongsToTenantDocument;

    protected string $collection = 'webhook_events';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'metadata' => 'array',
            'status' => WebhookEventStatus::class,
            'timestamp' => 'integer',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
