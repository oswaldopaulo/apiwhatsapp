<?php

declare(strict_types=1);

namespace App\Queue\Contracts;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Queue\QueueReservation;

interface MessageQueueRecorderInterface
{
    public function recordQueued(OutboundMessageData $message, QueueReservation $reservation): void;
}
