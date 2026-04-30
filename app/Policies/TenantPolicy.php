<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

final class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return $tenant->owner_user_id === $user->getKey();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $tenant->owner_user_id === $user->getKey();
    }
}
