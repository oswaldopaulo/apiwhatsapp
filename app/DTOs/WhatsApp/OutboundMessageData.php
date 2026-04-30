<?php

declare(strict_types=1);

namespace App\DTOs\WhatsApp;

final readonly class OutboundMessageData
{
    public function __construct(
        public string $tenantId,
        public string $whatsAppAccountId,
        public string $recipient,
        public string $body,
        public ?string $clientReference = null,
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
            'metadata' => $this->metadata,
        ];
    }
}
