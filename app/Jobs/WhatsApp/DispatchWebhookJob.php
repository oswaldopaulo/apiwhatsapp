<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Models\TenantConfiguration;
use App\Services\Webhooks\Contracts\OutgoingWebhookLogStoreInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $outgoingWebhookLogId,
    ) {
        $this->onQueue(config('queue-control.queues.webhooks', 'webhooks'));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(OutgoingWebhookLogStoreInterface $logs): void
    {
        $delivery = $logs->find($this->outgoingWebhookLogId)
            ?? throw new RuntimeException("Outgoing webhook delivery [{$this->outgoingWebhookLogId}] was not found.");

        $secret = $this->secretForTenant($delivery->tenantId);
        $timestamp = now()->timestamp;
        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => "sha256={$signature}",
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-Event' => $delivery->event,
            'X-Webhook-Delivery-Id' => $delivery->deliveryId,
        ];

        $logs->markSending($delivery->id, $headers);

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($delivery->url);

            if ($response->successful()) {
                $logs->markDelivered($delivery->id, $response);

                return;
            }

            $logs->markFailed($delivery->id, $response);

            throw new RuntimeException("Outgoing webhook failed with status [{$response->status()}].");
        } catch (Throwable $exception) {
            if (! isset($response)) {
                $logs->markFailed($delivery->id, exception: $exception);
            }

            throw $exception;
        }
    }

    private function secretForTenant(string|int $tenantId): string
    {
        /** @var TenantConfiguration|null $configuration */
        $configuration = TenantConfiguration::query()
            ->where('tenant_id', $tenantId)
            ->first();

        return (string) ($configuration?->webhook_secret ?: config('whatsapp.webhooks.secret', ''));
    }
}
