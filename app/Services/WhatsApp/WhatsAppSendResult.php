<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

final readonly class WhatsAppSendResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $provider,
        public string $providerMessageId,
        public array $metadata = [],
    ) {
    }
}
