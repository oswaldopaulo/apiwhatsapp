<?php

declare(strict_types=1);

namespace App\Queue;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Events\WhatsApp\MessageQueued;
use App\Events\WhatsApp\MessageWaiting;
use App\Events\WhatsApp\QueueCongested;
use App\Events\WhatsApp\QueueUpdated;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Queue\Contracts\MessageQueueRecorderInterface;
use App\Queue\Contracts\QueueControlInterface;
use App\Services\Audit\AuditService;
use App\Services\Tenancy\TenantConfigurationService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class QueueManagerService
{
    public function __construct(
        private QueueControlInterface $control,
        private QueueDelayCalculator $delayCalculator,
        private QueuePositionEstimator $positionEstimator,
        private SessionQueueLock $sessionLock,
        private TenantConfigurationService $configurationService,
        private TenantContext $tenantContext,
        private MessageQueueRecorderInterface $messageRecorder,
        private AuditService $audit,
    ) {
    }

    public function enqueue(OutboundMessageData $message): QueueReservation
    {
        $tenant = $this->resolveTenant($message);
        $configuration = $this->configurationService->getForTenant($tenant);
        $sessionId = $message->sessionId();
        $messageId = $message->messageId ?? (string) Str::uuid();

        /** @var QueueReservation $reservation */
        $reservation = $this->sessionLock->run(
            $tenant->getKey(),
            $sessionId,
            function () use ($tenant, $sessionId, $configuration, $messageId): QueueReservation {
                $now = CarbonImmutable::now();
                $lastScheduledAt = $this->control->lastScheduledAt($tenant->getKey(), $sessionId);
                $delaySeconds = $this->delayCalculator->calculate($configuration, $lastScheduledAt, $now);
                $scheduledAt = $now->addSeconds($delaySeconds);

                $this->control->storeLastScheduledAt($tenant->getKey(), $sessionId, $scheduledAt);

                return new QueueReservation(
                    messageId: $messageId,
                    tenantId: $tenant->getKey(),
                    sessionId: $sessionId,
                    delaySeconds: $delaySeconds,
                    queuePositionSnapshot: $this->positionEstimator->estimate($this->control, $tenant->getKey(), $sessionId),
                    scheduledAt: $scheduledAt,
                    controlDriver: $this->control->driverName(),
                );
            },
        );

        $queuedMessage = $message->withQueueReservation($reservation);
        $this->messageRecorder->recordQueued($queuedMessage, $reservation);
        $this->broadcastQueueEvents($reservation);

        $pendingDispatch = SendMessageJob::dispatch(
            $reservation->messageId,
            $reservation->tenantId,
            $reservation->sessionId,
        )
            ->delay($reservation->delaySeconds)
            ->onQueue(config('whatsapp.queue.name', 'messages'));

        $connection = $this->queueConnection($configuration);

        if ($connection !== null) {
            $pendingDispatch->onConnection($connection);
        }

        return $reservation;
    }

    private function broadcastQueueEvents(QueueReservation $reservation): void
    {
        event(new MessageQueued(
            $reservation->tenantId,
            $reservation->messageId,
            $reservation->sessionId,
            $reservation->queuePositionSnapshot,
            $reservation->delaySeconds,
        ));

        if ($reservation->delaySeconds > 0) {
            event(new MessageWaiting(
                $reservation->tenantId,
                $reservation->messageId,
                $reservation->sessionId,
                $reservation->delaySeconds,
            ));
        }

        event(new QueueUpdated(
            $reservation->tenantId,
            $reservation->sessionId,
            $reservation->queuePositionSnapshot,
            $reservation->delaySeconds,
        ));

        $threshold = (int) config('queue-control.throttling.congestion_position_threshold', 100);

        if ($reservation->queuePositionSnapshot >= $threshold) {
            event(new QueueCongested(
                $reservation->tenantId,
                $reservation->sessionId,
                $reservation->queuePositionSnapshot,
                $threshold,
            ));
            $this->audit->record('queue.congested', 'warning', [
                'session_id' => $reservation->sessionId,
                'queue_position_snapshot' => $reservation->queuePositionSnapshot,
                'threshold' => $threshold,
            ], $reservation->tenantId);
        }
    }

    private function resolveTenant(OutboundMessageData $message): Tenant
    {
        if ($this->tenantContext->hasTenant()) {
            return $this->tenantContext->current();
        }

        return Tenant::query()
            ->whereKey($message->tenantId)
            ->orWhere('public_id', $message->tenantId)
            ->firstOrFail();
    }

    private function queueConnection(TenantConfiguration $configuration): ?string
    {
        $driver = $configuration->queue_driver->value;

        if ($driver === 'default') {
            $driver = (string) config('queue.default', 'database');

            return in_array($driver, ['redis', 'database'], true) ? $driver : null;
        }

        if (! in_array($driver, ['redis', 'database'], true)) {
            throw new RuntimeException("Unsupported WhatsApp queue driver [{$driver}].");
        }

        return $driver;
    }
}
