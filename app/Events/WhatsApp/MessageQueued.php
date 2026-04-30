<?php

declare(strict_types=1);

namespace App\Events\WhatsApp;

use App\DTOs\WhatsApp\OutboundMessageData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MessageQueued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OutboundMessageData $message,
    ) {
    }
}
