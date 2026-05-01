<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Services\Webhooks\OutgoingWebhookService;

final readonly class DispatchOutgoingWebhook
{
    public function __construct(
        private OutgoingWebhookService $webhooks,
    ) {
    }

    public function handle(object $event): void
    {
        if (! property_exists($event, 'tenantId') || ! method_exists($event, 'broadcastAs') || ! method_exists($event, 'broadcastWith')) {
            return;
        }

        $this->webhooks->queue(
            $event->tenantId,
            $event->broadcastAs(),
            $event->broadcastWith(),
        );
    }
}
