<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Enums\WhatsAppSessionStatus;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppSession;
use App\Queue\Contracts\MessageQueueRecorderInterface;
use App\Queue\QueueReservation;
use App\Services\Audit\Contracts\AuditLogStoreInterface;
use App\Services\Security\ApiSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class AdvancedSecurityTest extends TestCase
{
    use RefreshDatabase;

    private SecurityAuditLogStoreFake $auditLogs;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->auditLogs = new SecurityAuditLogStoreFake();
        $this->app->instance(AuditLogStoreInterface::class, $this->auditLogs);
    }

    public function test_api_requires_json_accept_header(): void
    {
        $tenant = $this->createTenant();

        $this->get('/api/me', ['X-Tenant-ID' => $tenant->public_id])
            ->assertNotAcceptable()
            ->assertJsonPath('message', 'The API only supports JSON responses.');
    }

    public function test_api_responses_include_security_headers(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:read']);

        $this->getJson('/api/me', ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_message_send_is_rate_limited_by_session_id(): void
    {
        Queue::fake();
        config([
            'api-security.rate_limits.ip_per_minute' => 1000,
            'api-security.rate_limits.sensitive_per_minute' => 1000,
            'api-security.rate_limits.tenant_per_minute' => 1000,
            'api-security.rate_limits.session_per_minute' => 1,
        ]);

        $this->app->instance(MessageQueueRecorderInterface::class, new SecurityMessageQueueRecorderFake());

        [$user, $tenant] = $this->createUserWithTenantRole('owner');
        $this->createTenantConfiguration($tenant);
        $session = $this->createSession($tenant, WhatsAppSessionStatus::Connected);

        Passport::actingAs($user, ['messages:send']);

        $payload = [
            'session_id' => (string) $session->id,
            'to' => '5511999999999',
            'type' => 'text',
            'content' => 'mensagem',
        ];

        $this->postJson('/api/v1/messages/send', $payload, ['X-Tenant-ID' => $tenant->public_id])
            ->assertAccepted();

        $this->postJson('/api/v1/messages/send', $payload, ['X-Tenant-ID' => $tenant->public_id])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');

        $entry = $this->findAuditEntry('rate_limit.abuse');

        $this->assertNotNull($entry);
        $this->assertSame('session', $entry['metadata']['profile']);
    }

    public function test_security_service_revokes_current_token_after_repeated_abuse(): void
    {
        config([
            'api-security.rate_limits.ip_per_minute' => 0,
            'api-security.rate_limits.abuse_revocation_threshold' => 2,
        ]);

        $user = User::factory()->create();
        $token = new RevokableScopeToken();
        $user->withAccessToken($token);
        $request = Request::create('/api/v1/messages/send', 'POST', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setUserResolver(fn (): User => $user);

        app(ApiSecurityService::class)->rateLimit($request, 'ip');
        app(ApiSecurityService::class)->rateLimit($request, 'ip');

        $this->assertTrue($token->revoked);
        $this->assertNotNull($this->findAuditEntry('oauth.token_revoked'));
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

    /**
     * @return array<string, mixed>|null
     */
    private function findAuditEntry(string $action): ?array
    {
        foreach ($this->auditLogs->entries as $entry) {
            if ($entry['action'] === $action) {
                return $entry;
            }
        }

        return null;
    }
}

final class SecurityMessageQueueRecorderFake implements MessageQueueRecorderInterface
{
    public function recordQueued(OutboundMessageData $message, QueueReservation $reservation): void
    {
    }
}

final class SecurityAuditLogStoreFake implements AuditLogStoreInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    public array $entries = [];

    public function record(array $entry): void
    {
        $this->entries[] = $entry;
    }
}

final class RevokableScopeToken implements ScopeAuthorizable
{
    public string $id = 'revokable-token-id';

    public bool $revoked = false;

    public function can(string $scope): bool
    {
        return true;
    }

    public function cant(string $scope): bool
    {
        return false;
    }

    public function revoke(): void
    {
        $this->revoked = true;
    }
}
