<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Jobs\WhatsApp\DispatchWebhookJob;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Services\Webhooks\Contracts\OutgoingWebhookLogStoreInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final readonly class OutgoingWebhookService
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_EVENTS = [
        'message.queued',
        'message.waiting',
        'message.processing',
        'message.sent',
        'message.delivered',
        'message.failed',
        'message.received',
    ];

    public function __construct(
        private OutgoingWebhookLogStoreInterface $logs,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function queue(string|int $tenantId, string $event, array $payload): ?OutgoingWebhookDelivery
    {
        if (! in_array($event, self::SUPPORTED_EVENTS, true)) {
            return null;
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->find($tenantId);

        if ($tenant === null) {
            return null;
        }

        $configuration = TenantConfiguration::query()
            ->where('tenant_id', $tenant->getKey())
            ->first();

        if ($configuration === null || blank($configuration->webhook_url)) {
            return null;
        }

        $delivery = $this->logs->createPending(
            $tenant,
            (string) Str::uuid(),
            $event,
            (string) $configuration->webhook_url,
            $this->safePayload($event, $payload),
        );

        DispatchWebhookJob::dispatch($delivery->id)
            ->onQueue(config('queue-control.queues.webhooks', 'webhooks'));

        return $delivery;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function safePayload(string $event, array $payload): array
    {
        $safe = [
            'event' => $event,
            'message_id' => Arr::get($payload, 'message_id'),
            'session_id' => Arr::get($payload, 'session_id'),
            'status' => Arr::get($payload, 'status'),
            'provider_message_id' => Arr::get($payload, 'provider_message_id'),
            'queue_position_snapshot' => Arr::get($payload, 'queue_position_snapshot'),
            'delay_seconds' => Arr::get($payload, 'delay_seconds'),
            'from' => Arr::get($payload, 'from'),
            'type' => Arr::get($payload, 'type'),
            'error_message' => Arr::get($payload, 'error_message'),
        ];

        $safe = array_filter($safe, static fn (mixed $value): bool => $value !== null);
        $maxBytes = (int) config('whatsapp.outgoing_webhooks.max_payload_bytes', 16_384);
        $encoded = json_encode($safe, JSON_THROW_ON_ERROR);

        if (strlen($encoded) <= $maxBytes) {
            return $safe;
        }

        return array_filter([
            'event' => $event,
            'message_id' => Arr::get($payload, 'message_id'),
            'session_id' => Arr::get($payload, 'session_id'),
            'status' => Arr::get($payload, 'status'),
            'payload_truncated' => true,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
