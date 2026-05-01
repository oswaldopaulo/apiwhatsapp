<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\WhatsApp\MessageFailed;
use App\Events\WhatsApp\MessageProcessing;
use App\Events\WhatsApp\MessageSent;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\Mongo\Message;
use App\Queue\Contracts\MessageLogWriterInterface;
use App\Queue\Contracts\MessageStoreInterface;
use App\Services\WhatsApp\Contracts\WhatsAppProviderInterface;
use App\Services\WhatsApp\WhatsAppSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;
use Throwable;

final class SendMessageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_message_processing_sends_and_marks_sent(): void
    {
        Event::fake();

        $message = $this->message();
        $store = new InMemoryMessageStore($message);
        $logs = new InMemoryMessageLogWriter();

        $this->app->instance(MessageStoreInterface::class, $store);
        $this->app->instance(MessageLogWriterInterface::class, $logs);
        $this->app->instance(WhatsAppProviderInterface::class, new SuccessfulProvider());

        app()->call([new SendMessageJob('message-1', 10, 'session-1'), 'handle']);

        $this->assertSame(['processing', 'sent'], $store->transitions);
        $this->assertSame('provider-message-1', $store->message->provider_message_id);
        $this->assertSame(['info:message.processing', 'info:message.sent'], $logs->events);

        Event::assertDispatched(MessageProcessing::class);
        Event::assertDispatched(MessageSent::class);
        Event::assertNotDispatched(MessageFailed::class);
    }

    public function test_it_does_not_mark_failed_before_final_attempt(): void
    {
        Event::fake();

        $store = new InMemoryMessageStore($this->message());
        $logs = new InMemoryMessageLogWriter();

        $this->app->instance(MessageStoreInterface::class, $store);
        $this->app->instance(MessageLogWriterInterface::class, $logs);
        $this->app->instance(WhatsAppProviderInterface::class, new FailingProvider());

        $this->expectException(RuntimeException::class);

        try {
            app()->call([new SendMessageJob('message-1', 10, 'session-1'), 'handle']);
        } finally {
            $this->assertSame(['processing'], $store->transitions);
            $this->assertSame(['info:message.processing', 'warning:message.send_retry_scheduled'], $logs->events);
            $this->assertSame('Temporary provider outage with token=[redacted]', $logs->contexts[1]['error']);
            Event::assertDispatched(MessageProcessing::class);
            Event::assertNotDispatched(MessageFailed::class);
        }
    }

    public function test_it_marks_failed_on_final_attempt(): void
    {
        Event::fake();

        $store = new InMemoryMessageStore($this->message());
        $logs = new InMemoryMessageLogWriter();
        $job = new FinalAttemptSendMessageJob('message-1', 10, 'session-1');

        $this->app->instance(MessageStoreInterface::class, $store);
        $this->app->instance(MessageLogWriterInterface::class, $logs);
        $this->app->instance(WhatsAppProviderInterface::class, new FailingProvider());

        $this->expectException(RuntimeException::class);

        try {
            app()->call([$job, 'handle']);
        } finally {
            $this->assertSame(['processing', 'failed'], $store->transitions);
            $this->assertSame(['info:message.processing', 'error:message.failed'], $logs->events);
            Event::assertDispatched(MessageFailed::class);
        }
    }

    private function message(): Message
    {
        return new Message([
            'message_id' => 'message-1',
            'tenant_id' => 10,
            'session_id' => 'session-1',
            'to' => '5511999999999',
            'provider' => 'fake',
            'attempts' => 0,
            'metadata' => [],
        ]);
    }
}

final class FinalAttemptSendMessageJob extends SendMessageJob
{
    public function attempts(): int
    {
        return 3;
    }
}

final class SuccessfulProvider implements WhatsAppProviderInterface
{
    public function send(Message $message): WhatsAppSendResult
    {
        return new WhatsAppSendResult('fake', 'provider-message-1');
    }
}

final class FailingProvider implements WhatsAppProviderInterface
{
    public function send(Message $message): WhatsAppSendResult
    {
        throw new RuntimeException('Temporary provider outage with token=secret');
    }
}

final class InMemoryMessageStore implements MessageStoreInterface
{
    /**
     * @var list<string>
     */
    public array $transitions = [];

    public function __construct(
        public Message $message,
    ) {
    }

    public function findByMessageId(string $messageId): ?Message
    {
        return $messageId === $this->message->message_id ? $this->message : null;
    }

    public function markProcessing(Message $message): Message
    {
        $this->transitions[] = 'processing';
        $this->message->status = 'processing';
        $this->message->attempts = ((int) $this->message->attempts) + 1;

        return $this->message;
    }

    public function markSent(Message $message, WhatsAppSendResult $result): Message
    {
        $this->transitions[] = 'sent';
        $this->message->status = 'sent';
        $this->message->provider_message_id = $result->providerMessageId;

        return $this->message;
    }

    public function markFailed(Message $message, Throwable $exception): Message
    {
        $this->transitions[] = 'failed';
        $this->message->status = 'failed';
        $this->message->error_message = $exception->getMessage();

        return $this->message;
    }
}

final class InMemoryMessageLogWriter implements MessageLogWriterInterface
{
    /**
     * @var list<string>
     */
    public array $events = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $contexts = [];

    public function debug(Message $message, string $event, array $context = []): void
    {
        $this->events[] = "debug:{$event}";
        $this->contexts[] = $context;
    }

    public function info(Message $message, string $event, array $context = []): void
    {
        $this->events[] = "info:{$event}";
        $this->contexts[] = $context;
    }

    public function warning(Message $message, string $event, array $context = []): void
    {
        $this->events[] = "warning:{$event}";
        $this->contexts[] = $context;
    }

    public function error(Message $message, string $event, array $context = []): void
    {
        $this->events[] = "error:{$event}";
        $this->contexts[] = $context;
    }
}
