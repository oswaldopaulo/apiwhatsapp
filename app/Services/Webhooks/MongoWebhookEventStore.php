<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Enums\WebhookEventStatus;
use App\Models\Mongo\WebhookEvent;
use App\Models\Tenant;
use App\Services\Webhooks\Contracts\WebhookEventStoreInterface;
use Throwable;

final class MongoWebhookEventStore implements WebhookEventStoreInterface
{
    public function recordReceived(
        Tenant $tenant,
        string $eventType,
        string $rawBody,
        array $payload,
        array $headers,
        int $timestamp,
        string $signatureHash,
    ): WebhookEventRecord {
        /** @var WebhookEvent $event */
        $event = WebhookEvent::query()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'event_type' => $eventType,
            'provider' => 'whatsapp',
            'headers' => $this->sanitizeHeaders($headers),
            'raw_body' => $rawBody,
            'payload' => $payload,
            'status' => WebhookEventStatus::Received->value,
            'received_at' => now(),
            'metadata' => [
                'timestamp' => $timestamp,
                'signature_hash' => $signatureHash,
                'payload_tenant_id_present' => array_key_exists('tenant_id', $payload),
            ],
        ]);

        return $this->recordFromModel($event);
    }

    public function find(string $id): ?WebhookEventRecord
    {
        /** @var WebhookEvent|null $event */
        $event = WebhookEvent::query()->whereKey($id)->first();

        return $event === null ? null : $this->recordFromModel($event);
    }

    public function markProcessing(string $id): void
    {
        WebhookEvent::query()->whereKey($id)->update([
            'status' => WebhookEventStatus::Processing->value,
        ]);
    }

    public function markProcessed(string $id): void
    {
        WebhookEvent::query()->whereKey($id)->update([
            'status' => WebhookEventStatus::Processed->value,
            'processed_at' => now(),
        ]);
    }

    public function markIgnored(string $id, string $reason): void
    {
        WebhookEvent::query()->whereKey($id)->update([
            'status' => WebhookEventStatus::Ignored->value,
            'processed_at' => now(),
            'metadata.ignored_reason' => $reason,
        ]);
    }

    public function markFailed(string $id, Throwable $exception): void
    {
        WebhookEvent::query()->whereKey($id)->update([
            'status' => WebhookEventStatus::Failed->value,
            'failed_at' => now(),
            'metadata.error' => mb_substr($exception->getMessage(), 0, 500),
        ]);
    }

    private function recordFromModel(WebhookEvent $event): WebhookEventRecord
    {
        return new WebhookEventRecord(
            id: (string) $event->getKey(),
            tenantId: $event->tenant_id,
            eventType: (string) $event->event_type,
            payload: is_array($event->payload) ? $event->payload : [],
        );
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    private function sanitizeHeaders(array $headers): array
    {
        foreach ($headers as $key => $value) {
            if (preg_match('/authorization|cookie|secret|token|signature/i', (string) $key) === 1) {
                $headers[$key] = ['[redacted]'];
            }
        }

        return $headers;
    }
}
