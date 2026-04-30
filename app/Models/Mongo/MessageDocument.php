<?php

declare(strict_types=1);

namespace App\Models\Mongo;

final class MessageDocument extends MongoModel
{
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
