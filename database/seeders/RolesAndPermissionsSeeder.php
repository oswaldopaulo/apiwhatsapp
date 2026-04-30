<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'owner' => [
            'send messages',
            'read messages',
            'manage sessions',
            'read stats',
            'manage webhooks',
            'manage config',
        ],
        'admin' => [
            'send messages',
            'read messages',
            'manage sessions',
            'read stats',
            'manage webhooks',
            'manage config',
        ],
        'operator' => [
            'send messages',
            'read messages',
            'manage sessions',
        ],
        'readonly' => [
            'read messages',
            'read stats',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach ($this->permissions() as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'api');
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function permissions(): array
    {
        return [
            'send messages',
            'read messages',
            'manage sessions',
            'read stats',
            'manage webhooks',
            'manage config',
        ];
    }
}
