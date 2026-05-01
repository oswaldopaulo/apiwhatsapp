<?php

declare(strict_types=1);

namespace App\Events\WhatsApp;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SessionQrUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string|int $tenantId,
        public readonly string|int $sessionId,
        public readonly ?string $qrReference = null,
        public readonly ?int $expiresInSeconds = null,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("tenant.{$this->tenantId}.sessions");
    }

    public function broadcastAs(): string
    {
        return 'session.qr_updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'status' => 'qr_pending',
            'qr_reference' => $this->qrReference,
            'expires_in_seconds' => $this->expiresInSeconds,
        ];
    }
}
