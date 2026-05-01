<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Audit\Contracts\AuditLogStoreInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogStoreFake $auditLogs;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->auditLogs = new AuditLogStoreFake();
        $this->app->instance(AuditLogStoreInterface::class, $this->auditLogs);
    }

    public function test_audit_service_sanitizes_sensitive_metadata_and_masks_phone(): void
    {
        app(AuditService::class)->record('example.action', 'success', [
            'token' => 'plain-token',
            'webhook_secret' => 'plain-secret',
            'to' => '5511999999999',
            'content' => 'full message body',
            'nested' => ['authorization' => 'Bearer secret'],
        ], tenant: 10, user: 20);

        $metadata = $this->auditLogs->entries[0]['metadata'];

        $this->assertSame('[redacted]', $metadata['token']);
        $this->assertSame('[redacted]', $metadata['webhook_secret']);
        $this->assertSame('[redacted]', $metadata['content']);
        $this->assertSame('[redacted]', $metadata['nested']['authorization']);
        $this->assertSame('5511*******99', $metadata['to']);
        $this->assertSame('10', $this->auditLogs->entries[0]['tenant_id']);
        $this->assertSame('20', $this->auditLogs->entries[0]['user_id']);
    }

    public function test_configuration_update_is_audited_without_secret_value(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['config:write']);

        $this->putJson('/api/v1/config', [
            'webhook_url' => 'https://client.test/webhook',
            'webhook_secret' => 'super-secret-value',
        ], [
            'X-Tenant-ID' => $tenant->public_id,
            'User-Agent' => 'AuditTest/1.0',
        ])->assertOk();

        $entry = $this->findEntry('configuration.updated');

        $this->assertNotNull($entry);
        $this->assertSame((string) $tenant->id, $entry['tenant_id']);
        $this->assertSame((string) $user->id, $entry['user_id']);
        $this->assertTrue($entry['metadata']['webhook_secret_updated']);
        $this->assertStringNotContainsString('super-secret-value', json_encode($entry, JSON_THROW_ON_ERROR));
    }

    public function test_invalid_webhook_is_audited(): void
    {
        $tenant = $this->createTenant();

        $this->call(
            'POST',
            '/api/v1/webhooks/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_TENANT_ID' => $tenant->public_id,
                'HTTP_X_WEBHOOK_TIMESTAMP' => (string) now()->timestamp,
                'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=invalid',
            ],
            json_encode(['event' => 'message.sent'], JSON_THROW_ON_ERROR),
        )->assertUnauthorized();

        $entry = $this->findEntry('webhook.invalid');

        $this->assertNotNull($entry);
        $this->assertSame('blocked', $entry['status']);
        $this->assertSame('secret_not_configured', $entry['metadata']['reason']);
    }

    public function test_cross_tenant_attempt_is_audited(): void
    {
        [$user] = $this->createUserWithTenantRole('owner');
        $otherTenant = $this->createTenant('Other Tenant');

        Passport::actingAs($user, ['messages:read']);

        $this->getJson('/api/me', ['X-Tenant-ID' => $otherTenant->public_id])
            ->assertForbidden();

        $entry = $this->findEntry('tenant.cross_access_attempt');

        $this->assertNotNull($entry);
        $this->assertSame((string) $otherTenant->id, $entry['tenant_id']);
        $this->assertSame((string) $user->id, $entry['user_id']);
    }

    public function test_token_revocation_attempt_is_audited_without_raw_token(): void
    {
        [$user] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:read']);

        $this->deleteJson('/oauth/tokens/plain-token-id')
            ->assertNotFound();

        $entry = $this->findEntry('oauth.token_revoked');

        $this->assertNotNull($entry);
        $this->assertSame('not_found', $entry['status']);
        $this->assertArrayHasKey('token_id_hash', $entry['metadata']);
        $this->assertStringNotContainsString('plain-token-id', json_encode($entry, JSON_THROW_ON_ERROR));
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

    /**
     * @return array<string, mixed>|null
     */
    private function findEntry(string $action): ?array
    {
        foreach ($this->auditLogs->entries as $entry) {
            if ($entry['action'] === $action) {
                return $entry;
            }
        }

        return null;
    }
}

final class AuditLogStoreFake implements AuditLogStoreInterface
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
