<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\WhatsApp\MessageQueued;
use App\Events\WhatsApp\MessageWaiting;
use App\Events\WhatsApp\QueueUpdated;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Queue\Contracts\MessageQueueRecorderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\Passport;
use Tests\Support\CreatesApiTenants;
use Tests\Support\Fakes\InMemoryMessageQueueRecorder;
use Tests\TestCase;

final class WhatsAppApiCoreSuiteTest extends TestCase
{
    use CreatesApiTenants;
    use RefreshDatabase;

    private InMemoryMessageQueueRecorder $messageRecorder;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seedApiRolesAndPermissions();

        $this->messageRecorder = new InMemoryMessageQueueRecorder();
        $this->app->instance(MessageQueueRecorderInterface::class, $this->messageRecorder);
    }

    public function test_core_json_flow_uses_factories_queue_fake_and_event_fake(): void
    {
        Queue::fake();
        Event::fake();

        [$user, $tenant] = $this->createUserWithTenantRole('owner');
        $this->createTenantConfiguration($tenant, [
            'anti_ban_enabled' => false,
            'delay_min_seconds' => 0,
            'delay_max_seconds' => 0,
        ]);
        $session = $this->createWhatsAppSession($tenant, attributes: [
            'name' => 'Main Session',
            'phone_number' => '5511999999999',
        ]);

        Passport::actingAs($user, ['config:read', 'sessions:manage', 'messages:send']);

        $headers = ['X-Tenant-ID' => $tenant->public_id];

        $this->getJson('/api/v1/config', $headers)
            ->assertOk()
            ->assertJsonPath('data.tenant_id', $tenant->id);

        $this->getJson('/api/v1/sessions', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $session->id)
            ->assertJsonMissingPath('data.0.encrypted_credentials');

        $response = $this->postJson('/api/v1/messages/send', [
            'session_id' => (string) $session->id,
            'to' => '5511888888888',
            'type' => 'text',
            'content' => 'mensagem de teste',
        ], $headers)
            ->assertAccepted()
            ->assertJsonPath('data.status', 'queued');

        $messageId = (string) $response->json('data.message_id');

        $this->assertCount(1, $this->messageRecorder->records);
        $this->assertSame($messageId, $this->messageRecorder->records[0]['reservation']->messageId);
        $this->assertSame((string) $tenant->id, (string) $this->messageRecorder->records[0]['reservation']->tenantId);

        Queue::assertPushed(SendMessageJob::class, function (SendMessageJob $job) use ($messageId, $tenant, $session): bool {
            return $job->messageId === $messageId
                && (string) $job->tenantId === (string) $tenant->id
                && $job->sessionId === (string) $session->id;
        });

        Event::assertDispatched(MessageQueued::class);
        Event::assertDispatched(QueueUpdated::class);
        Event::assertNotDispatched(MessageWaiting::class);
    }

    public function test_core_json_flow_blocks_cross_tenant_access(): void
    {
        [$user] = $this->createUserWithTenantRole('owner');
        [$otherUser, $otherTenant] = $this->createUserWithTenantRole('owner');
        $this->createTenantConfiguration($otherTenant);

        Passport::actingAs($user, ['config:read']);

        $this->getJson('/api/v1/config', ['X-Tenant-ID' => $otherTenant->public_id])
            ->assertForbidden();

        $this->assertNotSame($user->id, $otherUser->id);
    }
}
