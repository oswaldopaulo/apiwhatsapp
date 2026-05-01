<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

final readonly class WebhookEventRecord
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string|int $tenantId,
        public string $eventType,
        public array $payload,
    ) {
    }
}
