<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\Mongo\Message;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Support\Str;

final class FakeWhatsAppProvider implements WhatsAppProviderInterface
{
    public function send(Message $message): WhatsAppSendResult
    {
        return new WhatsAppSendResult(
            provider: (string) ($message->provider ?: 'fake'),
            providerMessageId: 'fake_'.Str::uuid()->toString(),
            metadata: [
                'provider_stub' => true,
            ],
        );
    }
}
