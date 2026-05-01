<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Enums\MessageLogLevel;
use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class MessageLog extends MongoModel
{
    use BelongsToTenantDocument;

    protected string $collection = 'message_logs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => MessageLogLevel::class,
            'context' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
