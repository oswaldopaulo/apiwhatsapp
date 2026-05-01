<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Contracts;

use App\Models\Mongo\Message;
use App\Services\WhatsApp\WhatsAppSendResult;

interface WhatsAppProviderInterface
{
    public function send(Message $message): WhatsAppSendResult;
}
