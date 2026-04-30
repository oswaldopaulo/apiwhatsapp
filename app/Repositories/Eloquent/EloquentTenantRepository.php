<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepository;

final class EloquentTenantRepository implements TenantRepository
{
    public function find(string|int $id): ?Tenant
    {
        return Tenant::query()->find($id);
    }

    public function findByPublicId(string $publicId): ?Tenant
    {
        return Tenant::query()
            ->where('public_id', $publicId)
            ->first();
    }
}
