<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Events\WhatsApp\MessageFailed;
use App\Events\WhatsApp\MessageProcessing;
use App\Events\WhatsApp\MessageSent;
use App\Models\Mongo\Message;
use App\Queue\Contracts\MessageLogWriterInterface;
use App\Queue\Contracts\MessageStoreInterface;
use App\Queue\Middleware\SessionRateLimited;
use App\Services\Audit\AuditService;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Throwable;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly string $messageId,
        public readonly string|int|null $tenantId = null,
        public readonly ?string $sessionId = null,
    ) {
        $this->onQueue(config('whatsapp.queue.name', 'messages'));
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        if ($this->tenantId === null || $this->sessionId === null) {
            return [];
        }

        return [
            new SessionRateLimited($this->tenantId, $this->sessionId),
        ];
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(
        MessageStoreInterface $messages,
        MessageLogWriterInterface $logs,
        WhatsAppProviderInterface $provider,
        AuditService $audit,
    ): void {
        $message = $messages->findByMessageId($this->messageId)
            ?? throw (new ModelNotFoundException())->setModel(Message::class, [$this->messageId]);

        $this->validateMessage($message);

        $message = $messages->markProcessing($message);
        $logs->info($message, 'message.processing', [
            'attempt' => $this->attempts(),
        ]);

        event(new MessageProcessing($message->tenant_id, $message->message_id, $message->session_id));

        try {
            $result = $provider->send($message);
            $message = $messages->markSent($message, $result);

            $logs->info($message, 'message.sent', [
                'provider' => $result->provider,
                'provider_message_id' => $result->providerMessageId,
            ]);

            event(new MessageSent($message->tenant_id, $message->message_id, $message->session_id, $result->providerMessageId));
        } catch (Throwable $exception) {
            if (! $this->isFinalAttempt()) {
                $logs->warning($message, 'message.send_retry_scheduled', [
                    'attempt' => $this->attempts(),
                    'error' => $this->safeExceptionMessage($exception),
                ]);

                throw $exception;
            }

            $message = $messages->markFailed($message, $exception);

            $logs->error($message, 'message.failed', [
                'attempt' => $this->attempts(),
                'error' => $this->safeExceptionMessage($exception),
            ]);
            $audit->criticalFailure('message.send_failed', $exception, [
                'message_id' => $message->message_id,
                'session_id' => $message->session_id,
                'attempt' => $this->attempts(),
            ], $message->tenant_id);

            event(new MessageFailed($message->tenant_id, $message->message_id, $message->session_id, $this->safeExceptionMessage($exception)));

            throw $exception;
        }
    }

    private function validateMessage(Message $message): void
    {
        if (empty($message->tenant_id)) {
            throw new InvalidArgumentException('Message tenant_id is required.');
        }

        if (empty($message->session_id)) {
            throw new InvalidArgumentException('Message session_id is required.');
        }

        if ($this->tenantId !== null && (string) $message->tenant_id !== (string) $this->tenantId) {
            throw new InvalidArgumentException('Message tenant_id does not match the queued tenant.');
        }

        if ($this->sessionId !== null && (string) $message->session_id !== (string) $this->sessionId) {
            throw new InvalidArgumentException('Message session_id does not match the queued session.');
        }
    }

    private function isFinalAttempt(): bool
    {
        return max(1, $this->attempts()) >= $this->tries;
    }

    private function safeExceptionMessage(Throwable $exception): string
    {
        $message = mb_substr($exception->getMessage(), 0, 500);

        return preg_replace(
            '/\b(token|secret|password|api[_-]?key|access[_-]?token|refresh[_-]?token|authorization)\s*[:=]\s*[^\s,;]+/i',
            '$1=[redacted]',
            $message,
        ) ?? '[redacted]';
    }
}
