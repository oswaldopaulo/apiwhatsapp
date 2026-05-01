<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

$canAccessTenant = static function (User $user, string|int $tenantId): bool {
    $tenant = Tenant::query()->find($tenantId);

    if ($tenant === null) {
        return false;
    }

    $isOwner = $tenant->owner_user_id !== null && (string) $tenant->owner_user_id === (string) $user->getKey();
    $isMember = $user->tenants()->whereKey($tenant->getKey())->exists();

    return $isOwner || $isMember;
};

Broadcast::channel('tenant.{tenantId}.messages', $canAccessTenant);
Broadcast::channel('tenant.{tenantId}.sessions', $canAccessTenant);
Broadcast::channel('tenant.{tenantId}.queue', $canAccessTenant);
