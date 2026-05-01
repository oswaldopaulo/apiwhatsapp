<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\WhatsApp\ProcessWebhookJob;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Services\Webhooks\Contracts\WebhookEventStoreInterface;
use App\Services\Webhooks\WebhookEventRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

final class WhatsAppProviderWebhookTest extends TestCase
{
    use RefreshDatabase;

    private WebhookEventStoreFake $events;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();

        $this->events = new WebhookEventStoreFake();
        $this->app->instance(WebhookEventStoreInterface::class, $this->events);
    }

    public function test_it_accepts_valid_signature_records_raw_payload_and_dispatches_job(): void
    {
        [$tenant, $secret] = $this->createTenantWithWebhookSecret();
        $payload = [
            'event' => 'message.delivered',
            'tenant_id' => 'payload-tenant-is-ignored',
            'message_id' => 'message-1',
            'session_id' => 'session-1',
        ];

        $response = $this->signedWebhookRequest($tenant, $secret, $payload);

        $response
            ->assertAccepted()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.webhook_event_id', 'webhook-1');

        $this->assertCount(1, $this->events->received);
        $this->assertSame((string) $tenant->id, (string) $this->events->received[0]['tenant_id']);
        $this->assertSame('message.delivered', $this->events->received[0]['event_type']);
        $this->assertSame(json_encode($payload, JSON_THROW_ON_ERROR), $this->events->received[0]['raw_body']);

        Queue::assertPushed(ProcessWebhookJob::class, function (ProcessWebhookJob $job): bool {
            return $job->webhookEventId === 'webhook-1';
        });
    }

    public function test_it_rejects_invalid_signature(): void
    {
        [$tenant] = $this->createTenantWithWebhookSecret();
        $payload = ['event' => 'message.sent', 'message_id' => 'message-1', 'session_id' => 'session-1'];
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->callWebhook($tenant, $rawBody, now()->timestamp, 'sha256=invalid-signature')
            ->assertUnauthorized();

        $this->assertSame([], $this->events->received);
        Queue::assertNotPushed(ProcessWebhookJob::class);
    }

    public function test_it_rejects_replay_attack(): void
    {
        [$tenant, $secret] = $this->createTenantWithWebhookSecret();
        $payload = ['event' => 'session.connected', 'session_id' => 'session-1'];
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = $this->signature($secret, $timestamp, $rawBody);

        $this->callWebhook($tenant, $rawBody, $timestamp, $signature)
            ->assertAccepted();

        $this->callWebhook($tenant, $rawBody, $timestamp, $signature)
            ->assertConflict()
            ->assertSee('Webhook replay detected.');

        $this->assertCount(1, $this->events->received);
        Queue::assertPushed(ProcessWebhookJob::class, 1);
    }

    /**
     * @return array{Tenant, string}
     */
    private function createTenantWithWebhookSecret(): array
    {
        $secret = 'tenant-webhook-secret';
        $tenant = Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Tenant',
        ]);

        TenantConfiguration::query()->create([
            'tenant_id' => $tenant->id,
            'webhook_secret' => $secret,
        ]);

        return [$tenant, $secret];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function signedWebhookRequest(Tenant $tenant, string $secret, array $payload)
    {
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;

        return $this->callWebhook($tenant, $rawBody, $timestamp, $this->signature($secret, $timestamp, $rawBody));
    }

    private function callWebhook(Tenant $tenant, string $rawBody, int $timestamp, string $signature)
    {
        return $this->call(
            'POST',
            '/api/v1/webhooks/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_TENANT_ID' => $tenant->public_id,
                'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            ],
            $rawBody,
        );
    }

    private function signature(string $secret, int $timestamp, string $rawBody): string
    {
        return 'sha256='.hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);
    }
}

final class WebhookEventStoreFake implements WebhookEventStoreInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $received = [];

    /**
     * @var array<string, WebhookEventRecord>
     */
    public array $records = [];

    public function recordReceived(
        Tenant $tenant,
        string $eventType,
        string $rawBody,
        array $payload,
        array $headers,
        int $timestamp,
        string $signatureHash,
    ): WebhookEventRecord {
        $id = 'webhook-'.(count($this->records) + 1);
        $record = new WebhookEventRecord($id, $tenant->getKey(), $eventType, $payload);

        $this->records[$id] = $record;
        $this->received[] = [
            'id' => $id,
            'tenant_id' => $tenant->getKey(),
            'event_type' => $eventType,
            'raw_body' => $rawBody,
            'payload' => $payload,
            'headers' => $headers,
            'timestamp' => $timestamp,
            'signature_hash' => $signatureHash,
        ];

        return $record;
    }

    public function find(string $id): ?WebhookEventRecord
    {
        return $this->records[$id] ?? null;
    }

    public function markProcessing(string $id): void
    {
    }

    public function markProcessed(string $id): void
    {
    }

    public function markIgnored(string $id, string $reason): void
    {
    }

    public function markFailed(string $id, Throwable $exception): void
    {
    }
}
