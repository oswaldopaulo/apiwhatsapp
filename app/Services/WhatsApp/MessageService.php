<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Queue\QueueManagerService;
use App\Queue\QueueReservation;
use App\Support\Tenancy\TenantContext;

final readonly class MessageService
{
    public function __construct(
        private QueueManagerService $queueManager,
        private SessionService $sessions,
        private TenantContext $tenantContext,
    ) {
    }

    public function send(OutboundMessageData $message): QueueReservation
    {
        if ($this->tenantContext->hasTenant()) {
            $this->sessions->ensureCanSend($this->tenantContext->current(), $message->sessionId());
        }

        return $this->queueManager->enqueue($message);
    }
}
