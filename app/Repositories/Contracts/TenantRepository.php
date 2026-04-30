<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface TenantRepository
{
    public function find(string|int $id): ?Tenant;

    public function findByPublicId(string $publicId): ?Tenant;
}
