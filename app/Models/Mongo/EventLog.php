<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class EventLog extends MongoModel
{
    use BelongsToTenantDocument;

    protected $table = 'event_logs';

    protected string $collection = 'event_logs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
