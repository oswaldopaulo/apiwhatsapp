<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Enums\OutgoingWebhookStatus;
use App\Models\Mongo\OutgoingWebhookLog;
use App\Models\Tenant;
use App\Services\Webhooks\Contracts\OutgoingWebhookLogStoreInterface;
use Illuminate\Http\Client\Response;
use Throwable;

final class MongoOutgoingWebhookLogStore implements OutgoingWebhookLogStoreInterface
{
    public function createPending(Tenant $tenant, string $deliveryId, string $event, string $url, array $payload): OutgoingWebhookDelivery
    {
        /** @var OutgoingWebhookLog $log */
        $log = OutgoingWebhookLog::query()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'delivery_id' => $deliveryId,
            'event' => $event,
            'webhook_url' => $url,
            'request_payload' => $payload,
            'request_headers' => [],
            'status' => OutgoingWebhookStatus::Pending->value,
            'attempts' => 0,
            'metadata' => [],
        ]);

        return $this->fromModel($log);
    }

    public function find(string $id): ?OutgoingWebhookDelivery
    {
        /** @var OutgoingWebhookLog|null $log */
        $log = OutgoingWebhookLog::query()->whereKey($id)->first();

        return $log === null ? null : $this->fromModel($log);
    }

    public function markSending(string $id, array $headers): OutgoingWebhookDelivery
    {
        /** @var OutgoingWebhookLog $log */
        $log = OutgoingWebhookLog::query()->whereKey($id)->firstOrFail();
        $log->forceFill([
            'status' => OutgoingWebhookStatus::Sending->value,
            'request_headers' => $headers,
            'attempts' => ((int) $log->attempts) + 1,
            'sent_at' => now(),
        ])->save();

        return $this->fromModel($log->refresh());
    }

    public function markDelivered(string $id, Response $response): void
    {
        OutgoingWebhookLog::query()->whereKey($id)->update([
            'status' => OutgoingWebhookStatus::Delivered->value,
            'response_status' => $response->status(),
            'response_headers' => $this->sanitizeHeaders($response->headers()),
            'delivered_at' => now(),
            'failed_at' => null,
            'next_retry_at' => null,
        ]);
    }

    public function markFailed(string $id, ?Response $response = null, ?Throwable $exception = null): void
    {
        OutgoingWebhookLog::query()->whereKey($id)->update([
            'status' => OutgoingWebhookStatus::Failed->value,
            'response_status' => $response?->status(),
            'response_headers' => $response === null ? [] : $this->sanitizeHeaders($response->headers()),
            'failed_at' => now(),
            'next_retry_at' => now()->addMinutes(2),
            'metadata.error' => $exception === null ? null : mb_substr($exception->getMessage(), 0, 500),
        ]);
    }

    private function fromModel(OutgoingWebhookLog $log): OutgoingWebhookDelivery
    {
        return new OutgoingWebhookDelivery(
            id: (string) $log->getKey(),
            deliveryId: (string) $log->delivery_id,
            tenantId: $log->tenant_id,
            event: (string) $log->event,
            url: (string) $log->webhook_url,
            payload: is_array($log->request_payload) ? $log->request_payload : [],
            attempts: (int) $log->attempts,
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
