<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('send messages', fn (User $user): bool => $this->allowsTenantPermission($user, 'send messages'));
        Gate::define('read messages', fn (User $user): bool => $this->allowsTenantPermission($user, 'read messages'));
        Gate::define('manage sessions', fn (User $user): bool => $this->allowsTenantPermission($user, 'manage sessions'));
        Gate::define('read stats', fn (User $user): bool => $this->allowsTenantPermission($user, 'read stats'));
        Gate::define('manage webhooks', fn (User $user): bool => $this->allowsTenantPermission($user, 'manage webhooks'));
        Gate::define('manage config', fn (User $user): bool => $this->allowsTenantPermission($user, 'manage config'));
    }

    private function allowsTenantPermission(User $user, string $permission): bool
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return false;
        }

        $isOwner = $tenant->owner_user_id !== null && (string) $tenant->owner_user_id === (string) $user->getKey();
        $isMember = $user->tenants()->whereKey($tenant->getKey())->exists();

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        return ($isOwner || $isMember) && $user->hasPermissionTo($permission, 'api');
    }
}
