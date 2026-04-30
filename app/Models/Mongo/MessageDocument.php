<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class MessageDocument extends MongoModel
{
    use BelongsToTenantDocument;

    protected $table = 'messages';

    protected string $collection = 'messages';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
