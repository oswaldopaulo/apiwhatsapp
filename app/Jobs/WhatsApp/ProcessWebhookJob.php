<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\Events\WhatsApp\MessageDelivered;
use App\Events\WhatsApp\MessageFailed;
use App\Events\WhatsApp\MessageReceived;
use App\Events\WhatsApp\MessageSent;
use App\Events\WhatsApp\SessionConnected;
use App\Events\WhatsApp\SessionDisconnected;
use App\Events\WhatsApp\SessionQrUpdated;
use App\Services\Webhooks\Contracts\WebhookEventStoreInterface;
use App\Services\Webhooks\WebhookEventRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly string $webhookEventId,
    ) {
        $this->onQueue(config('queue-control.queues.webhooks', 'webhooks'));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function handle(WebhookEventStoreInterface $events): void
    {
        $record = $events->find($this->webhookEventId)
            ?? throw new RuntimeException("Webhook event [{$this->webhookEventId}] was not found.");

        $events->markProcessing($record->id);

        try {
            $processed = $this->dispatchInternalEvent($record);

            if ($processed) {
                $events->markProcessed($record->id);

                return;
            }

            $events->markIgnored($record->id, "Unsupported webhook event [{$record->eventType}].");
        } catch (Throwable $exception) {
            $events->markFailed($record->id, $exception);

            throw $exception;
        }
    }

    private function dispatchInternalEvent(WebhookEventRecord $record): bool
    {
        $payload = $record->payload;
        $tenantId = $record->tenantId;
        $messageId = $this->messageId($payload);
        $sessionId = $this->sessionId($payload);

        match ($record->eventType) {
            'message.received' => event(new MessageReceived(
                $tenantId,
                $messageId,
                $sessionId,
                $this->nullableString(Arr::get($payload, 'from')),
                $this->nullableString(Arr::get($payload, 'message.type', Arr::get($payload, 'type'))),
            )),
            'message.sent' => event(new MessageSent(
                $tenantId,
                $messageId,
                $sessionId,
                $this->nullableString(Arr::get($payload, 'provider_message_id')),
            )),
            'message.delivered' => event(new MessageDelivered(
                $tenantId,
                $messageId,
                $sessionId,
                $this->nullableString(Arr::get($payload, 'provider_message_id')),
            )),
            'message.failed' => event(new MessageFailed(
                $tenantId,
                $messageId,
                $sessionId,
                $this->safeErrorMessage($payload),
            )),
            'session.connected' => event(new SessionConnected(
                $tenantId,
                $sessionId,
                (int) Arr::get($payload, 'risk_score', 0),
            )),
            'session.disconnected' => event(new SessionDisconnected(
                $tenantId,
                $sessionId,
                (int) Arr::get($payload, 'risk_score', 40),
            )),
            'session.qr' => event(new SessionQrUpdated(
                $tenantId,
                $sessionId,
                $this->qrReference($payload),
                $this->nullableInt(Arr::get($payload, 'expires_in_seconds')),
            )),
            default => null,
        };

        return in_array($record->eventType, [
            'message.received',
            'message.sent',
            'message.delivered',
            'message.failed',
            'session.connected',
            'session.disconnected',
            'session.qr',
        ], true);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function messageId(array $payload): string
    {
        return (string) (
            Arr::get($payload, 'message_id')
            ?? Arr::get($payload, 'message.id')
            ?? Arr::get($payload, 'provider_message_id')
            ?? Str::uuid()
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function sessionId(array $payload): string
    {
        return (string) (
            Arr::get($payload, 'session_id')
            ?? Arr::get($payload, 'session.id')
            ?? 'unknown'
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function safeErrorMessage(array $payload): string
    {
        $message = (string) (
            Arr::get($payload, 'error_message')
            ?? Arr::get($payload, 'error.message')
            ?? 'Provider reported message failure.'
        );

        return preg_replace(
            '/\b(token|secret|password|api[_-]?key|access[_-]?token|refresh[_-]?token|authorization)\s*[:=]\s*[^\s,;]+/i',
            '$1=[redacted]',
            mb_substr($message, 0, 500),
        ) ?? '[redacted]';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function qrReference(array $payload): ?string
    {
        $reference = $this->nullableString(Arr::get($payload, 'qr_reference'));

        if ($reference !== null) {
            return $reference;
        }

        $qr = $this->nullableString(Arr::get($payload, 'qr'));

        return $qr === null ? null : hash('sha256', $qr);
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
