<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

use App\Queue\QueueReservation;

final readonly class OutboundMessageData
{
    public function __construct(
        public string $tenantId,
        public string $whatsAppAccountId,
        public string $recipient,
        public string $body,
        public ?string $clientReference = null,
        public ?string $sessionId = null,
        public ?string $messageId = null,
        public ?int $delaySeconds = null,
        public ?int $queuePositionSnapshot = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            tenantId: (string) $payload['tenant_id'],
            whatsAppAccountId: (string) $payload['whatsapp_account_id'],
            recipient: (string) $payload['recipient'],
            body: (string) $payload['body'],
            clientReference: isset($payload['client_reference']) ? (string) $payload['client_reference'] : null,
            sessionId: isset($payload['session_id']) ? (string) $payload['session_id'] : null,
            messageId: isset($payload['message_id']) ? (string) $payload['message_id'] : null,
            delaySeconds: isset($payload['delay_seconds']) ? (int) $payload['delay_seconds'] : null,
            queuePositionSnapshot: isset($payload['queue_position_snapshot']) ? (int) $payload['queue_position_snapshot'] : null,
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'whatsapp_account_id' => $this->whatsAppAccountId,
            'recipient' => $this->recipient,
            'body' => $this->body,
            'client_reference' => $this->clientReference,
            'session_id' => $this->sessionId,
            'message_id' => $this->messageId,
            'delay_seconds' => $this->delaySeconds,
            'queue_position_snapshot' => $this->queuePositionSnapshot,
            'metadata' => $this->metadata,
        ];
    }

    public function sessionId(): string
    {
        return $this->sessionId ?? $this->whatsAppAccountId;
    }

    public function withQueueReservation(QueueReservation $reservation): self
    {
        return new self(
            tenantId: (string) $reservation->tenantId,
            whatsAppAccountId: $this->whatsAppAccountId,
            recipient: $this->recipient,
            body: $this->body,
            clientReference: $this->clientReference,
            sessionId: $reservation->sessionId,
            messageId: $reservation->messageId,
            delaySeconds: $reservation->delaySeconds,
            queuePositionSnapshot: $reservation->queuePositionSnapshot,
            metadata: [
                ...$this->metadata,
                'scheduled_at' => $reservation->scheduledAt->toJSON(),
                'queue_control_driver' => $reservation->controlDriver,
            ],
        );
    }
}
