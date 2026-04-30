<?php

declare(strict_types=1);

namespace App\Models\Mongo;

final class WebhookDelivery extends MongoModel
{
    protected $table = 'webhook_deliveries';

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
