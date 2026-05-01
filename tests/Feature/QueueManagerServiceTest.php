<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Queue\Contracts\MessageQueueRecorderInterface;
use App\Queue\QueueManagerService;
use App\Queue\QueueReservation;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class QueueManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reserves_delay_records_mongo_status_and_dispatches_job(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 12:00:00'));
        Queue::fake();

        $recorder = new InMemoryMessageQueueRecorder();
        $this->app->instance(MessageQueueRecorderInterface::class, $recorder);

        $tenant = Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Tenant',
        ]);

        TenantConfiguration::query()->create([
            'tenant_id' => $tenant->id,
            'queue_driver' => 'database',
            'redis_enabled' => false,
            'anti_ban_enabled' => true,
            'delay_min_seconds' => 5,
            'delay_max_seconds' => 5,
            'max_messages_per_minute' => 60,
            'max_daily_messages' => 1000,
            'settings' => [],
        ]);

        app(TenantContext::class)->run($tenant, function (): void {
            app(QueueManagerService::class)->enqueue(new OutboundMessageData(
                tenantId: 'ignored-while-context-exists',
                whatsAppAccountId: 'account-1',
                recipient: '5511999999999',
                body: 'Hello',
                sessionId: 'session-1',
            ));
        });

        Queue::assertPushed(SendMessageJob::class, function (SendMessageJob $job) use ($tenant): bool {
            return (string) $job->tenantId === (string) $tenant->id
                && $job->sessionId === 'session-1'
                && $job->messageId === $this->recordsMessageId()
                && $job->delay === 5;
        });

        $this->assertCount(1, $recorder->records);
        $this->assertSame(5, $recorder->records[0]['reservation']->delaySeconds);
        $this->assertSame('session-1', $recorder->records[0]['reservation']->sessionId);

        CarbonImmutable::setTestNow();
    }

    private function recordsMessageId(): string
    {
        /** @var InMemoryMessageQueueRecorder $recorder */
        $recorder = $this->app->make(MessageQueueRecorderInterface::class);

        return $recorder->records[0]['reservation']->messageId;
    }
}

final class InMemoryMessageQueueRecorder implements MessageQueueRecorderInterface
{
    /**
     * @var list<array{message: OutboundMessageData, reservation: QueueReservation}>
     */
    public array $records = [];

    public function recordQueued(OutboundMessageData $message, QueueReservation $reservation): void
    {
        $this->records[] = [
            'message' => $message,
            'reservation' => $reservation,
        ];
    }
}
