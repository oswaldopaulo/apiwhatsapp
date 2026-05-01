<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use App\Enums\OutgoingWebhookStatus;
use App\Models\Mongo\Concerns\BelongsToTenantDocument;

final class OutgoingWebhookLog extends MongoModel
{
    use BelongsToTenantDocument;

    protected string $collection = 'outgoing_webhook_logs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OutgoingWebhookStatus::class,
            'request_headers' => 'array',
            'request_payload' => 'array',
            'response_headers' => 'array',
            'metadata' => 'array',
            'response_status' => 'integer',
            'attempts' => 'integer',
            'next_retry_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @param array<string, mixed> $value
     */
    public function setRequestHeadersAttribute(array $value): void
    {
        foreach ($value as $key => $header) {
            if (preg_match('/authorization|cookie|secret|token|signature|api-key/i', (string) $key) === 1) {
                unset($value[$key]);
            }
        }

        $this->attributes['request_headers'] = json_encode($value, JSON_THROW_ON_ERROR);
    }
}
