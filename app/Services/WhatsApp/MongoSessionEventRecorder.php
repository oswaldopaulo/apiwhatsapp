<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\SessionEventType;
use App\Models\Mongo\SessionEvent;
use App\Models\WhatsApp\WhatsAppSession;
use App\Services\WhatsApp\Contracts\SessionEventRecorderInterface;

final class MongoSessionEventRecorder implements SessionEventRecorderInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function record(WhatsAppSession $session, SessionEventType $eventType, array $payload = []): void
    {
        SessionEvent::query()->create([
            'tenant_id' => (string) $session->tenant_id,
            'session_id' => (string) $session->getKey(),
            'event_type' => $eventType->value,
            'payload' => $payload,
            'metadata' => [
                'provider' => $session->provider,
                'status' => $session->status->value,
                'risk_score' => $session->risk_score,
                'phone_number_configured' => filled($session->phone_number),
            ],
            'occurred_at' => now(),
        ]);
    }
}
