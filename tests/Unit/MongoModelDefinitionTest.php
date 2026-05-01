<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\MessageStatus;
use App\Models\Mongo\Message;
use App\Models\Mongo\OutgoingWebhookLog;
use App\Models\Mongo\QueueEvent;
use App\Models\Mongo\SessionEvent;
use App\Models\Mongo\WebhookEvent;
use Tests\TestCase;

final class MongoModelDefinitionTest extends TestCase
{
    public function test_mongo_models_use_expected_collections(): void
    {
        $this->assertSame('messages', (new Message())->getTable());
        $this->assertSame('webhook_events', (new WebhookEvent())->getTable());
        $this->assertSame('session_events', (new SessionEvent())->getTable());
        $this->assertSame('queue_events', (new QueueEvent())->getTable());
        $this->assertSame('outgoing_webhook_logs', (new OutgoingWebhookLog())->getTable());
    }

    public function test_message_factory_builds_required_shape(): void
    {
        $message = Message::factory()->make();

        $this->assertNotEmpty($message->message_id);
        $this->assertNotEmpty($message->tenant_id);
        $this->assertNotEmpty($message->session_id);
        $this->assertNotEmpty($message->to);
        $this->assertSame(MessageStatus::Queued, $message->status);
        $this->assertIsArray($message->content);
        $this->assertIsArray($message->metadata);
    }

    public function test_outgoing_webhook_log_strips_sensitive_request_headers(): void
    {
        $log = new OutgoingWebhookLog();

        $log->request_headers = [
            'Authorization' => 'Bearer secret',
            'X-Api-Key' => 'secret',
            'content-type' => 'application/json',
        ];

        $this->assertSame(['content-type' => 'application/json'], $log->request_headers);
    }
}
