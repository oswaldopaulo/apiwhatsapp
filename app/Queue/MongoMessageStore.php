<?php

declare(strict_types=1);

namespace App\Queue;

use App\Enums\MessageStatus;
use App\Models\Mongo\Message;
use App\Queue\Contracts\MessageStoreInterface;
use App\Services\WhatsApp\WhatsAppSendResult;
use Throwable;

final class MongoMessageStore implements MessageStoreInterface
{
    public function findByMessageId(string $messageId): ?Message
    {
        return Message::query()
            ->where('message_id', $messageId)
            ->first();
    }

    public function markProcessing(Message $message): Message
    {
        $message->forceFill([
            'status' => MessageStatus::Processing->value,
            'processing_at' => now(),
            'attempts' => ((int) $message->attempts) + 1,
        ])->save();

        return $message->refresh();
    }

    public function markSent(Message $message, WhatsAppSendResult $result): Message
    {
        $message->forceFill([
            'status' => MessageStatus::Sent->value,
            'provider' => $result->provider,
            'provider_message_id' => $result->providerMessageId,
            'sent_at' => now(),
            'error_code' => null,
            'error_message' => null,
            'metadata' => [
                ...($message->metadata ?? []),
                'provider_result' => $result->metadata,
            ],
        ])->save();

        return $message->refresh();
    }

    public function markFailed(Message $message, Throwable $exception): Message
    {
        $message->forceFill([
            'status' => MessageStatus::Failed->value,
            'failed_at' => now(),
            'error_code' => (string) ($exception->getCode() ?: 'send_failed'),
            'error_message' => $this->safeMessage($exception),
        ])->save();

        return $message->refresh();
    }

    private function safeMessage(Throwable $exception): string
    {
        return mb_substr($exception->getMessage(), 0, 500);
    }
}
