<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\WhatsApp\MessageDelivered;
use App\Events\WhatsApp\MessageFailed;
use App\Events\WhatsApp\MessageProcessing;
use App\Events\WhatsApp\MessageQueued;
use App\Events\WhatsApp\MessageReceived;
use App\Events\WhatsApp\MessageSent;
use App\Events\WhatsApp\MessageWaiting;
use App\Events\WhatsApp\QueueCongested;
use App\Events\WhatsApp\QueueUpdated;
use App\Events\WhatsApp\SessionConnected;
use App\Events\WhatsApp\SessionDisconnected;
use App\Events\WhatsApp\SessionQrUpdated;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Tests\TestCase;

final class BroadcastEventContractTest extends TestCase
{
    public function test_message_events_broadcast_on_private_tenant_message_channel_with_safe_payloads(): void
    {
        $events = [
            new MessageQueued(10, 'message-1', 'session-1', 2, 5),
            new MessageWaiting(10, 'message-1', 'session-1', 5),
            new MessageProcessing(10, 'message-1', 'session-1'),
            new MessageSent(10, 'message-1', 'session-1', 'provider-1'),
            new MessageDelivered(10, 'message-1', 'session-1', 'provider-1'),
            new MessageFailed(10, 'message-1', 'session-1', 'temporary error'),
            new MessageReceived(10, 'message-2', 'session-1', '5511888888888', 'text'),
        ];

        foreach ($events as $event) {
            $this->assertInstanceOf(ShouldBroadcast::class, $event);
            $this->assertSame('private-tenant.10.messages', (string) $event->broadcastOn());
            $this->assertArrayNotHasKey('content', $event->broadcastWith());
            $this->assertArrayNotHasKey('webhook_secret', $event->broadcastWith());
            $this->assertArrayNotHasKey('encrypted_credentials', $event->broadcastWith());
        }
    }

    public function test_session_events_broadcast_on_private_tenant_session_channel(): void
    {
        $events = [
            new SessionConnected(10, 20, 0),
            new SessionDisconnected(10, 20, 40),
            new SessionQrUpdated(10, 20, 'qr-reference', 60),
        ];

        foreach ($events as $event) {
            $this->assertInstanceOf(ShouldBroadcast::class, $event);
            $this->assertSame('private-tenant.10.sessions', (string) $event->broadcastOn());
            $this->assertArrayNotHasKey('encrypted_credentials', $event->broadcastWith());
        }
    }

    public function test_queue_events_broadcast_on_private_tenant_queue_channel(): void
    {
        $events = [
            new QueueUpdated(10, 'session-1', 3, 8),
            new QueueCongested(10, 'session-1', 100, 80),
        ];

        foreach ($events as $event) {
            $this->assertInstanceOf(ShouldBroadcast::class, $event);
            $this->assertSame('private-tenant.10.queue', (string) $event->broadcastOn());
            $this->assertArrayHasKey('queue_position_snapshot', $event->broadcastWith());
        }
    }
}
