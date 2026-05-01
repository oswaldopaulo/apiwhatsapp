<?php

declare(strict_types=1);

namespace App\Queue;

use App\Enums\MessageLogLevel;
use App\Models\Mongo\Message;
use App\Models\Mongo\MessageLog;
use App\Queue\Contracts\MessageLogWriterInterface;

final class MongoMessageLogWriter implements MessageLogWriterInterface
{
    public function debug(Message $message, string $event, array $context = []): void
    {
        $this->write($message, MessageLogLevel::Debug, $event, $context);
    }

    public function info(Message $message, string $event, array $context = []): void
    {
        $this->write($message, MessageLogLevel::Info, $event, $context);
    }

    public function warning(Message $message, string $event, array $context = []): void
    {
        $this->write($message, MessageLogLevel::Warning, $event, $context);
    }

    public function error(Message $message, string $event, array $context = []): void
    {
        $this->write($message, MessageLogLevel::Error, $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function write(Message $message, MessageLogLevel $level, string $event, array $context): void
    {
        MessageLog::query()->create([
            'tenant_id' => $message->tenant_id,
            'message_id' => $message->message_id,
            'level' => $level->value,
            'message' => $event,
            'context' => $this->sanitize($context),
            'metadata' => [
                'session_id' => $message->session_id,
                'provider' => $message->provider,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        foreach (['token', 'secret', 'api_key', 'access_token', 'refresh_token', 'authorization', 'Authorization'] as $key) {
            if (array_key_exists($key, $context)) {
                $context[$key] = '[redacted]';
            }
        }

        return $context;
    }
}
