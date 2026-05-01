<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\WhatsAppSessionStatus;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppSession;
use Spatie\Permission\PermissionRegistrar;

trait CreatesApiTenants
{
    protected function seedApiRolesAndPermissions(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /**
     * @return array{User, Tenant}
     */
    protected function createUserWithTenantRole(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
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

    protected function createTenantConfiguration(Tenant $tenant, array $attributes = []): TenantConfiguration
    {
        return TenantConfiguration::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
        ], $attributes));
    }

    protected function createWhatsAppSession(
        Tenant $tenant,
        WhatsAppSessionStatus $status = WhatsAppSessionStatus::Connected,
        array $attributes = [],
    ): WhatsAppSession {
        return WhatsAppSession::factory()
            ->status($status)
            ->create(array_merge([
                'tenant_id' => $tenant->id,
            ], $attributes));
    }
}
