<?php

declare(strict_types=1);

namespace App\Services\Webhooks\Contracts;

use App\Models\Tenant;
use App\Services\Webhooks\WebhookEventRecord;
use Throwable;

interface WebhookEventStoreInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $headers
     */
    public function recordReceived(
        Tenant $tenant,
        string $eventType,
        string $rawBody,
        array $payload,
        array $headers,
        int $timestamp,
        string $signatureHash,
    ): WebhookEventRecord;

    public function find(string $id): ?WebhookEventRecord;

    public function markProcessing(string $id): void;

    public function markProcessed(string $id): void;

    public function markIgnored(string $id, string $reason): void;

    public function markFailed(string $id, Throwable $exception): void;
}
