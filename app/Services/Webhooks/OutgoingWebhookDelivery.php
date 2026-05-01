<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

final readonly class OutgoingWebhookDelivery
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $deliveryId,
        public string|int $tenantId,
        public string $event,
        public string $url,
        public array $payload,
        public int $attempts = 0,
    ) {
    }
}
