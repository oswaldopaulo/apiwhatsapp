<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SessionEventType;
use App\Enums\WhatsAppSessionStatus;
use App\Events\WhatsApp\SessionCreated;
use App\Events\WhatsApp\SessionDeleted;
use App\Events\WhatsApp\SessionStatusChanged;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppSession;
use App\Services\WhatsApp\Contracts\SessionEventRecorderInterface;
use App\Services\WhatsApp\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class WhatsAppSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected SessionEventRecorderFake $events;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->events = new SessionEventRecorderFake();
        $this->app->instance(SessionEventRecorderInterface::class, $this->events);
    }

    public function test_it_creates_session_encrypts_credentials_and_records_event(): void
    {
        Event::fake();

        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['sessions:manage']);

        $response = $this->postJson('/api/v1/sessions', [
            'name' => 'Main WhatsApp',
            'provider' => 'fake',
            'status' => 'qr_pending',
            'phone_number' => '5511999999999',
            'metadata' => ['device' => 'android'],
            'credentials' => ['token' => 'super-secret-token'],
        ], [
            'X-Tenant-ID' => $tenant->public_id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Main WhatsApp')
            ->assertJsonPath('data.status', 'qr_pending')
            ->assertJsonMissingPath('data.encrypted_credentials');

        $sessionId = $response->json('data.id');
        $rawCredentials = DB::table('whatsapp_sessions')->where('id', $sessionId)->value('encrypted_credentials');

        $this->assertIsString($rawCredentials);
        $this->assertStringNotContainsString('super-secret-token', $rawCredentials);
        $this->assertSame(SessionEventType::Created, $this->events->records[0]['event_type']);

        Event::assertDispatched(SessionCreated::class);
    }

    public function test_it_lists_and_shows_only_current_tenant_sessions(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');
        $otherTenant = $this->createTenant('Other Tenant');
        $session = $this->createSession($tenant, 'Tenant Session', WhatsAppSessionStatus::Connected);
        $otherSession = $this->createSession($otherTenant, 'Other Session', WhatsAppSessionStatus::Connected);

        Passport::actingAs($user, ['sessions:manage']);

        $this->getJson('/api/v1/sessions', ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $session->id)
            ->assertJsonMissing(['id' => $otherSession->id]);

        $this->getJson("/api/v1/sessions/{$session->id}", ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonPath('data.id', $session->id);

        $this->getJson("/api/v1/sessions/{$otherSession->id}", ['X-Tenant-ID' => $tenant->public_id])
            ->assertNotFound();
    }

    public function test_it_deletes_session_and_records_event(): void
    {
        Event::fake();

        [$user, $tenant] = $this->createUserWithTenantRole('owner');
        $session = $this->createSession($tenant, 'Delete Me', WhatsAppSessionStatus::Connected);

        Passport::actingAs($user, ['sessions:manage']);

        $this->deleteJson("/api/v1/sessions/{$session->id}", [], ['X-Tenant-ID' => $tenant->public_id])
            ->assertNoContent();

        $this->assertDatabaseMissing('whatsapp_sessions', ['id' => $session->id]);
        $this->assertSame(SessionEventType::Deleted, $this->events->records[0]['event_type']);

        Event::assertDispatched(SessionDeleted::class);
    }

    public function test_it_requires_sessions_manage_scope(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:send']);

        $this->getJson('/api/v1/sessions', ['X-Tenant-ID' => $tenant->public_id])
            ->assertForbidden()
            ->assertJsonPath('required_scope', 'sessions:manage');
    }

    public function test_service_updates_status_recalculates_risk_and_emits_event(): void
    {
        Event::fake();

        $tenant = $this->createTenant();
        $session = $this->createSession($tenant, 'Status Session', WhatsAppSessionStatus::Connected);

        $updated = app(SessionService::class)->updateStatus($session, WhatsAppSessionStatus::Banned);

        $this->assertSame(WhatsAppSessionStatus::Banned, $updated->status);
        $this->assertSame(100, $updated->risk_score);
        $this->assertSame(SessionEventType::Banned, $this->events->records[0]['event_type']);

        Event::assertDispatched(SessionStatusChanged::class);
    }

    public function test_message_send_is_blocked_for_disconnected_session(): void
    {
        Queue::fake();

        [$user, $tenant] = $this->createUserWithTenantRole('owner');
        $this->createTenantConfiguration($tenant);
        $session = $this->createSession($tenant, 'Disconnected Session', WhatsAppSessionStatus::Disconnected);

        Passport::actingAs($user, ['messages:send']);

        $this->postJson('/api/v1/messages/send', [
            'session_id' => (string) $session->id,
            'to' => '5511999999999',
            'type' => 'text',
            'content' => 'mensagem',
        ], [
            'X-Tenant-ID' => $tenant->public_id,
        ])
            ->assertForbidden();

        Queue::assertNotPushed(SendMessageJob::class);
    }

    /**
     * @return array{User, Tenant}
     */
    private function createUserWithTenantRole(string $role): array
    {
        $user = User::factory()->create();
        $tenant = $this->createTenant(ownerUserId: $role === 'owner' ? $user->id : null);

        $tenant->users()->attach($user->id, ['role' => $role]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole($role);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return [$user, $tenant];
    }

    private function createTenant(string $name = 'Tenant', ?int $ownerUserId = null): Tenant
    {
        return Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => $name,
            'owner_user_id' => $ownerUserId,
        ]);
    }

    private function createSession(Tenant $tenant, string $name, WhatsAppSessionStatus $status): WhatsAppSession
    {
        return WhatsAppSession::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'provider' => 'fake',
            'status' => $status->value,
            'phone_number' => null,
            'risk_score' => $status->riskScore(),
            'metadata' => [],
        ]);
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
}

final class SessionEventRecorderFake implements SessionEventRecorderInterface
{
    /**
     * @var list<array{session_id: int|string, event_type: SessionEventType, payload: array<string, mixed>}>
     */
    public array $records = [];

    /**
     * @param array<string, mixed> $payload
     */
    public function record(WhatsAppSession $session, SessionEventType $eventType, array $payload = []): void
    {
        $this->records[] = [
            'session_id' => $session->getKey(),
            'event_type' => $eventType,
            'payload' => $payload,
        ];
    }
}
