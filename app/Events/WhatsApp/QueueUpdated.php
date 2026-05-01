<?php

declare(strict_types=1);

namespace App\Events\WhatsApp;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string|int $tenantId,
        public readonly string $sessionId,
        public readonly int $queuePositionSnapshot,
        public readonly int $delaySeconds,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("tenant.{$this->tenantId}.queue");
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'queue_position_snapshot' => $this->queuePositionSnapshot,
            'delay_seconds' => $this->delaySeconds,
        ];
    }
}
