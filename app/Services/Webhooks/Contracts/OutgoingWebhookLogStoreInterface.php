<?php

declare(strict_types=1);

namespace App\Services\Webhooks\Contracts;

use App\Models\Tenant;
use App\Services\Webhooks\OutgoingWebhookDelivery;
use Illuminate\Http\Client\Response;
use Throwable;

interface OutgoingWebhookLogStoreInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function createPending(Tenant $tenant, string $deliveryId, string $event, string $url, array $payload): OutgoingWebhookDelivery;

    public function find(string $id): ?OutgoingWebhookDelivery;

    /**
     * @param array<string, string> $headers
     */
    public function markSending(string $id, array $headers): OutgoingWebhookDelivery;

    public function markDelivered(string $id, Response $response): void;

    public function markFailed(string $id, ?Response $response = null, ?Throwable $exception = null): void;
}
