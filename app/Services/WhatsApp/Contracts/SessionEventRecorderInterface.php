<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Contracts;

use App\Enums\SessionEventType;
use App\Models\WhatsApp\WhatsAppSession;

interface SessionEventRecorderInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function record(WhatsAppSession $session, SessionEventType $eventType, array $payload = []): void;
}
