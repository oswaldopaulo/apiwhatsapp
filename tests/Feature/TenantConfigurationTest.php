<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Models\User;
use App\Services\Tenancy\TenantConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class TenantConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_can_read_current_tenant_configuration(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();

        Passport::actingAs($user, ['config:read']);

        $this->getJson('/api/v1/config', ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.queue_driver', 'default')
            ->assertJsonPath('data.webhook_secret_configured', false)
            ->assertJsonMissing(['webhook_secret' => 'secret']);
    }

    public function test_read_requires_config_read_scope(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();

        Passport::actingAs($user, ['config:write']);

        $this->getJson('/api/v1/config', ['X-Tenant-ID' => $tenant->public_id])
            ->assertForbidden()
            ->assertJsonPath('required_scope', 'config:read');
    }

    public function test_can_update_configuration_and_secret_is_encrypted_and_hidden(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();

        Passport::actingAs($user, ['config:write']);

        $this->putJson('/api/v1/config', [
            'queue_driver' => 'redis',
            'redis_enabled' => true,
            'anti_ban_enabled' => true,
            'delay_min_seconds' => 5,
            'delay_max_seconds' => 20,
            'max_messages_per_minute' => 30,
            'max_daily_messages' => 5000,
            'webhook_url' => 'https://example.com/webhook',
            'webhook_secret' => 'super-secret-value',
            'settings' => [
                'timezone' => 'America/Sao_Paulo',
            ],
        ], ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonPath('data.queue_driver', 'redis')
            ->assertJsonPath('data.redis_enabled', true)
            ->assertJsonPath('data.webhook_secret_configured', true)
            ->assertJsonMissing(['webhook_secret' => 'super-secret-value']);

        $configuration = TenantConfiguration::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertNotSame('super-secret-value', $configuration->getRawOriginal('webhook_secret'));
        $this->assertSame('super-secret-value', $configuration->webhook_secret);
    }

    public function test_update_rejects_invalid_limits(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();

        Passport::actingAs($user, ['config:write']);

        $this->putJson('/api/v1/config', [
            'delay_min_seconds' => 60,
            'delay_max_seconds' => 10,
            'max_messages_per_minute' => 0,
            'max_daily_messages' => 1000001,
        ], ['X-Tenant-ID' => $tenant->public_id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'delay_max_seconds',
                'max_messages_per_minute',
                'max_daily_messages',
            ]);
    }

    public function test_configuration_is_cached_and_invalidated_on_update(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();
        $service = app(TenantConfigurationService::class);

        Passport::actingAs($user, ['config:read', 'config:write']);

        $this->getJson('/api/v1/config', ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk();

        $this->assertTrue(Cache::has($service->cacheKey($tenant)));

        $this->putJson('/api/v1/config', [
            'queue_driver' => 'database',
            'delay_min_seconds' => 4,
            'delay_max_seconds' => 8,
        ], ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonPath('data.queue_driver', 'database');

        $this->assertFalse(Cache::has($service->cacheKey($tenant)));
    }

    /**
     * @return array{User, Tenant}
     */
    private function createUserAndTenant(): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Tenant',
            'owner_user_id' => $user->id,
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner']);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole('owner');
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return [$user, $tenant];
    }
}
