<?php

declare(strict_types=1);

namespace App\Models\Mongo;

final class EventLog extends MongoModel
{
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
