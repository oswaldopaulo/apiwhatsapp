<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class WebhookDelivery extends MongoModel
{
    use BelongsToTenantDocument;

    protected string $collection = 'webhook_deliveries';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
