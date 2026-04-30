<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class AuthorizationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_owner_role_can_send_messages_for_current_tenant(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:send']);

        $this->postJson('/api/messages', [], ['X-Tenant-ID' => $tenant->public_id])
            ->assertAccepted();
    }

    public function test_readonly_role_cannot_send_messages(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('readonly');

        Passport::actingAs($user, ['messages:send']);

        $this->postJson('/api/messages', [], ['X-Tenant-ID' => $tenant->public_id])
            ->assertForbidden();
    }

    public function test_permissions_are_isolated_by_tenant_id(): void
    {
        $user = User::factory()->create();
        $tenantA = $this->createTenant('Tenant A', $user->id);
        $tenantB = $this->createTenant('Tenant B', $user->id);

        $tenantA->users()->attach($user->id, ['role' => 'owner']);
        $tenantB->users()->attach($user->id, ['role' => 'readonly']);

        $this->assignRoleForTenant($user, $tenantA, 'owner');
        $this->assignRoleForTenant($user, $tenantB, 'readonly');

        app(TenantContext::class)->run($tenantA, function () use ($user): void {
            $this->assertTrue(Gate::forUser($user)->allows('send messages'));
            $this->assertTrue($user->hasPermissionTo('send messages', 'api'));
        });

        app(TenantContext::class)->run($tenantB, function () use ($user): void {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $this->assertFalse(Gate::forUser($user)->allows('send messages'));
            $this->assertFalse($user->hasPermissionTo('send messages', 'api'));
            $this->assertTrue($user->hasPermissionTo('read messages', 'api'));
        });
    }

    /**
     * @return array{User, Tenant}
     */
    private function createUserWithTenantRole(string $role): array
    {
        $user = User::factory()->create();
        $tenant = $this->createTenant(ownerUserId: $role === 'owner' ? $user->id : null);

        $tenant->users()->attach($user->id, ['role' => $role]);
        $this->assignRoleForTenant($user, $tenant, $role);

        return [$user, $tenant];
    }

    private function assignRoleForTenant(User $user, Tenant $tenant, string $role): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole($role);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
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
