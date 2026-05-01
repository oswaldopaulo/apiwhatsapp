<?php

declare(strict_types=1);

namespace App\Queue\Contracts;

use App\Models\Mongo\Message;
use App\Services\WhatsApp\WhatsAppSendResult;
use Throwable;

interface MessageStoreInterface
{
    public function findByMessageId(string $messageId): ?Message;

    public function markProcessing(Message $message): Message;

    public function markSent(Message $message, WhatsAppSendResult $result): Message;

    public function markFailed(Message $message, Throwable $exception): Message;
}
