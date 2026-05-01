<?php

declare(strict_types=1);

namespace App\Actions\WhatsApp;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Queue\QueueManagerService;
use App\Queue\QueueReservation;

final readonly class QueueOutboundMessageAction
{
    public function __construct(
        private QueueManagerService $queueManager,
    ) {
    }

    public function execute(OutboundMessageData $message): QueueReservation
    {
        return $this->queueManager->enqueue($message);
    }
}
