<?php

declare(strict_types=1);

namespace App\Actions\WhatsApp;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use Illuminate\Foundation\Bus\PendingDispatch;

final class QueueOutboundMessageAction
{
    public function execute(OutboundMessageData $message): PendingDispatch
    {
        return SendWhatsAppMessageJob::dispatch($message);
    }
}
