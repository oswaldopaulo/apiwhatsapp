<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Enums\WhatsAppSessionStatus;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppSession;
use App\Queue\Contracts\MessageQueueRecorderInterface;
use App\Queue\QueueReservation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SendMessageEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_it_accepts_message_records_it_and_dispatches_job(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01 12:00:00'));
        Queue::fake();

        $recorder = new SendEndpointMessageQueueRecorder();
        $this->app->instance(MessageQueueRecorderInterface::class, $recorder);

        [$user, $tenant] = $this->createUserWithTenantRole('owner');
        $this->createTenantConfiguration($tenant);
        $session = $this->createSession($tenant, WhatsAppSessionStatus::Connected);

        Passport::actingAs($user, ['messages:send']);

        $response = $this->postJson('/api/v1/messages/send', [
            'session_id' => (string) $session->id,
            'to' => '5511999999999',
            'type' => 'text',
            'content' => 'mensagem',
        ], [
            'X-Tenant-ID' => $tenant->public_id,
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.queue_position_snapshot', 1)
            ->assertJsonPath('data.delay_seconds', 0);

        $messageId = $response->json('data.message_id');

        $this->assertIsString($messageId);
        $this->assertCount(1, $recorder->records);
        $this->assertSame((string) $tenant->id, $recorder->records[0]['message']->tenantId);
        $this->assertSame((string) $session->id, $recorder->records[0]['message']->sessionId());
        $this->assertSame('5511999999999', $recorder->records[0]['message']->recipient);
        $this->assertSame('mensagem', $recorder->records[0]['message']->body);

        Queue::assertPushed(SendMessageJob::class, function (SendMessageJob $job) use ($tenant, $messageId, $session): bool {
            return $job->messageId === $messageId
                && (string) $job->tenantId === (string) $tenant->id
                && $job->sessionId === (string) $session->id;
        });

        CarbonImmutable::setTestNow();
    }

    public function test_it_requires_messages_send_scope(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:read']);

        $this->postJson('/api/v1/messages/send', [
            'session_id' => 'session-1',
            'to' => '5511999999999',
            'type' => 'text',
            'content' => 'mensagem',
        ], [
            'X-Tenant-ID' => $tenant->public_id,
        ])
            ->assertForbidden()
            ->assertJsonPath('required_scope', 'messages:send');
    }

    public function test_it_rejects_invalid_phone_number(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:send']);

        $this->postJson('/api/v1/messages/send', [
            'session_id' => 'session-1',
            'to' => '+55 11 99999-9999',
            'type' => 'text',
            'content' => 'mensagem',
        ], [
            'X-Tenant-ID' => $tenant->public_id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_it_rejects_tenant_id_from_payload(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:send']);

        $this->postJson('/api/v1/messages/send', [
            'tenant_id' => 'another-tenant',
            'session_id' => 'session-1',
            'to' => '5511999999999',
            'type' => 'text',
            'content' => 'mensagem',
        ], [
            'X-Tenant-ID' => $tenant->public_id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id']);
    }

    /**
     * @return array{User, Tenant}
     */
    private function createUserWithTenantRole(string $role): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Tenant',
            'owner_user_id' => $role === 'owner' ? $user->id : null,
        ]);

        $tenant->users()->attach($user->id, ['role' => $role]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole($role);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return [$user, $tenant];
    }

    private function createTenantConfiguration(Tenant $tenant): void
    {
        TenantConfiguration::query()->create([
            'tenant_id' => $tenant->id,
            'queue_driver' => 'database',
            'redis_enabled' => false,
            'anti_ban_enabled' => false,
            'delay_min_seconds' => 0,
            'delay_max_seconds' => 0,
            'max_messages_per_minute' => 60,
            'max_daily_messages' => 1000,
            'settings' => [],
        ]);
    }

    private function createSession(Tenant $tenant, WhatsAppSessionStatus $status): WhatsAppSession
    {
        return WhatsAppSession::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Session',
            'provider' => 'fake',
            'status' => $status->value,
            'risk_score' => $status->riskScore(),
            'metadata' => [],
        ]);
    }
}

final class SendEndpointMessageQueueRecorder implements MessageQueueRecorderInterface
{
    /**
     * @var list<array{message: OutboundMessageData, reservation: QueueReservation}>
     */
    public array $records = [];

    public function recordQueued(OutboundMessageData $message, QueueReservation $reservation): void
    {
        $this->records[] = [
            'message' => $message,
            'reservation' => $reservation,
        ];
    }
}
