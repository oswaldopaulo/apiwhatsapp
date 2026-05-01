<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Mongo\Concerns\BelongsToTenantDocument;
use Illuminate\Support\Str;

class Message extends MongoModel
{
    use BelongsToTenantDocument;

    protected string $collection = 'messages';

    protected static function booted(): void
    {
        static::creating(static function (self $message): void {
            if (empty($message->message_id)) {
                $message->message_id = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'content' => 'array',
            'status' => MessageStatus::class,
            'attempts' => 'integer',
            'queue_position_snapshot' => 'integer',
            'delay_seconds' => 'integer',
            'metadata' => 'array',
            'queued_at' => 'datetime',
            'waiting_at' => 'datetime',
            'processing_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'canceled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
