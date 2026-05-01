<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class BroadcastChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'redis']);
        require base_path('routes/channels.php');

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_member_can_authenticate_private_tenant_channels(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:read']);

        foreach (['messages', 'sessions', 'queue'] as $channel) {
            $this->postJson('/broadcasting/auth', [
                'socket_id' => '123.456',
                'channel_name' => "private-tenant.{$tenant->id}.{$channel}",
            ])->assertOk();
        }
    }

    public function test_user_from_another_tenant_cannot_authenticate_channel(): void
    {
        [$user] = $this->createUserWithTenantRole('owner');
        $otherTenant = $this->createTenant('Other Tenant');

        Passport::actingAs($user, ['messages:read']);

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-tenant.{$otherTenant->id}.messages",
        ])->assertForbidden();
    }

    public function test_channel_auth_requires_authenticated_api_user(): void
    {
        $tenant = $this->createTenant();

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-tenant.{$tenant->id}.messages",
        ])->assertUnauthorized();
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
}
