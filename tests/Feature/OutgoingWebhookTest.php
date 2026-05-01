<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\WhatsApp\MessageSent;
use App\Jobs\WhatsApp\DispatchWebhookJob;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Services\Webhooks\Contracts\OutgoingWebhookLogStoreInterface;
use App\Services\Webhooks\OutgoingWebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

final class OutgoingWebhookTest extends TestCase
{
    use RefreshDatabase;

    private OutgoingWebhookLogStoreFake $logs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logs = new OutgoingWebhookLogStoreFake();
        $this->app->instance(OutgoingWebhookLogStoreInterface::class, $this->logs);
    }

    public function test_message_event_queues_outgoing_webhook_when_tenant_has_url(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithWebhook('https://client.test/webhooks', 'client-secret');

        event(new MessageSent($tenant->id, 'message-1', 'session-1', 'provider-message-1'));

        $this->assertCount(1, $this->logs->deliveries);
        $this->assertSame('message.sent', $this->logs->deliveries['log-1']->event);
        $this->assertSame('https://client.test/webhooks', $this->logs->deliveries['log-1']->url);
        $this->assertSame('message-1', $this->logs->deliveries['log-1']->payload['message_id']);
        $this->assertArrayNotHasKey('content', $this->logs->deliveries['log-1']->payload);

        Queue::assertPushed(DispatchWebhookJob::class, function (DispatchWebhookJob $job): bool {
            return $job->outgoingWebhookLogId === 'log-1';
        });
    }

    public function test_message_event_does_not_queue_when_webhook_url_is_empty(): void
    {
        Queue::fake();

        $tenant = $this->createTenantWithWebhook(null, 'client-secret');

        event(new MessageSent($tenant->id, 'message-1', 'session-1'));

        $this->assertSame([], $this->logs->deliveries);
        Queue::assertNotPushed(DispatchWebhookJob::class);
    }

    public function test_dispatch_job_sends_signed_payload_and_marks_delivered(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 12:00:00'));
        Http::fake([
            'https://client.test/webhooks' => Http::response(['ok' => true], 200, ['X-Client' => 'accepted']),
        ]);

        $tenant = $this->createTenantWithWebhook('https://client.test/webhooks', 'client-secret');
        $delivery = $this->logs->createPending($tenant, 'delivery-1', 'message.sent', 'https://client.test/webhooks', [
            'event' => 'message.sent',
            'message_id' => 'message-1',
            'session_id' => 'session-1',
            'status' => 'sent',
        ]);

        app()->call([new DispatchWebhookJob($delivery->id), 'handle']);

        Http::assertSent(function (Request $request): bool {
            $timestamp = $request->header('X-Webhook-Timestamp')[0] ?? '';
            $signature = $request->header('X-Webhook-Signature')[0] ?? '';
            $expected = 'sha256='.hash_hmac('sha256', "{$timestamp}.{$request->body()}", 'client-secret');

            return $request->url() === 'https://client.test/webhooks'
                && $request->header('X-Webhook-Event')[0] === 'message.sent'
                && $request->header('X-Webhook-Delivery-Id')[0] === 'delivery-1'
                && $signature === $expected;
        });

        $this->assertSame(1, $this->logs->deliveries[$delivery->id]->attempts);
        $this->assertSame('delivered', $this->logs->statuses[$delivery->id]);
        $this->assertSame('[redacted]', $this->logs->sentHeaders[$delivery->id]['X-Webhook-Signature']);

        Carbon::setTestNow();
    }

    public function test_dispatch_job_marks_failed_and_retries_on_non_success_response(): void
    {
        Http::fake([
            'https://client.test/webhooks' => Http::response(['error' => true], 500),
        ]);

        $tenant = $this->createTenantWithWebhook('https://client.test/webhooks', 'client-secret');
        $delivery = $this->logs->createPending($tenant, 'delivery-1', 'message.failed', 'https://client.test/webhooks', [
            'event' => 'message.failed',
            'message_id' => 'message-1',
            'session_id' => 'session-1',
            'status' => 'failed',
            'error_message' => 'Provider failed.',
        ]);
        $job = new DispatchWebhookJob($delivery->id);

        $this->assertSame([30, 120, 300], $job->backoff());
        $this->expectException(\RuntimeException::class);

        try {
            app()->call([$job, 'handle']);
        } finally {
            $this->assertSame('failed', $this->logs->statuses[$delivery->id]);
            $this->assertSame(500, $this->logs->responseStatuses[$delivery->id]);
        }
    }

    private function createTenantWithWebhook(?string $url, string $secret): Tenant
    {
        $tenant = Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Tenant',
        ]);

        TenantConfiguration::query()->create([
            'tenant_id' => $tenant->id,
            'webhook_url' => $url,
            'webhook_secret' => $secret,
        ]);

        return $tenant;
    }
}

final class OutgoingWebhookLogStoreFake implements OutgoingWebhookLogStoreInterface
{
    /**
     * @var array<string, OutgoingWebhookDelivery>
     */
    public array $deliveries = [];

    /**
     * @var array<string, string>
     */
    public array $statuses = [];

    /**
     * @var array<string, array<string, string>>
     */
    public array $sentHeaders = [];

    /**
     * @var array<string, int>
     */
    public array $responseStatuses = [];

    public function createPending(Tenant $tenant, string $deliveryId, string $event, string $url, array $payload): OutgoingWebhookDelivery
    {
        $id = 'log-'.(count($this->deliveries) + 1);
        $delivery = new OutgoingWebhookDelivery($id, $deliveryId, $tenant->getKey(), $event, $url, $payload);

        $this->deliveries[$id] = $delivery;
        $this->statuses[$id] = 'pending';

        return $delivery;
    }

    public function find(string $id): ?OutgoingWebhookDelivery
    {
        return $this->deliveries[$id] ?? null;
    }

    public function markSending(string $id, array $headers): OutgoingWebhookDelivery
    {
        $this->statuses[$id] = 'sending';
        $this->sentHeaders[$id] = $this->sanitizeHeaders($headers);
        $delivery = $this->deliveries[$id];
        $delivery = new OutgoingWebhookDelivery(
            $delivery->id,
            $delivery->deliveryId,
            $delivery->tenantId,
            $delivery->event,
            $delivery->url,
            $delivery->payload,
            $delivery->attempts + 1,
        );

        return $this->deliveries[$id] = $delivery;
    }

    public function markDelivered(string $id, Response $response): void
    {
        $this->statuses[$id] = 'delivered';
        $this->responseStatuses[$id] = $response->status();
    }

    public function markFailed(string $id, ?Response $response = null, ?Throwable $exception = null): void
    {
        $this->statuses[$id] = 'failed';
        $this->responseStatuses[$id] = $response?->status() ?? 0;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function sanitizeHeaders(array $headers): array
    {
        foreach ($headers as $key => $value) {
            if (preg_match('/signature|authorization|secret|token/i', $key) === 1) {
                $headers[$key] = '[redacted]';
            }
        }

        return $headers;
    }
}
