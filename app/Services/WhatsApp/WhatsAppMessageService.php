<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Actions\WhatsApp\QueueOutboundMessageAction;
use App\DTOs\WhatsApp\OutboundMessageData;
use Illuminate\Foundation\Bus\PendingDispatch;

final readonly class WhatsAppMessageService
{
    public function __construct(
        private QueueOutboundMessageAction $queueOutboundMessage,
    ) {
    }

    public function queueOutboundMessage(OutboundMessageData $message): PendingDispatch
    {
        return $this->queueOutboundMessage->execute($message);
    }
}
