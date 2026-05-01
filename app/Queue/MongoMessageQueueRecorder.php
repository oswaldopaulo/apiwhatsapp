<?php

declare(strict_types=1);

namespace App\Queue;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Mongo\Message;
use App\Queue\Contracts\MessageQueueRecorderInterface;

final class MongoMessageQueueRecorder implements MessageQueueRecorderInterface
{
    public function recordQueued(OutboundMessageData $message, QueueReservation $reservation): void
    {
        Message::query()->updateOrCreate(
            [
                'tenant_id' => (string) $reservation->tenantId,
                'message_id' => $reservation->messageId,
            ],
            [
                'session_id' => $reservation->sessionId,
                'to' => $message->recipient,
                'type' => MessageType::Text->value,
                'content' => [
                    'text' => $message->body,
                ],
                'status' => MessageStatus::Queued->value,
                'provider' => $message->metadata['provider'] ?? 'whatsapp',
                'provider_message_id' => null,
                'queued_at' => now(),
                'waiting_at' => $reservation->delaySeconds > 0 ? now() : null,
                'attempts' => 0,
                'queue_position_snapshot' => $reservation->queuePositionSnapshot,
                'delay_seconds' => $reservation->delaySeconds,
                'metadata' => [
                    ...$message->metadata,
                    'queue_control_driver' => $reservation->controlDriver,
                    'scheduled_at' => $reservation->scheduledAt->toJSON(),
                ],
            ],
        );
    }
}
