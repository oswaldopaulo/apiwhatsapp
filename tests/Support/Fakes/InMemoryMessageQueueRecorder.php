<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Queue\Contracts\MessageQueueRecorderInterface;
use App\Queue\QueueReservation;

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
