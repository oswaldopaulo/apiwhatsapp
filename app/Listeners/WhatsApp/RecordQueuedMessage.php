<?php

declare(strict_types=1);

namespace App\Listeners\WhatsApp;

use App\Events\WhatsApp\MessageQueued;

final class RecordQueuedMessage
{
    public function handle(MessageQueued $event): void
    {
        // MongoDB persistence/activity logging will be wired here.
    }
}
