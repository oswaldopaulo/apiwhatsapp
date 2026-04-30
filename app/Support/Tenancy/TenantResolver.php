<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepository;
use Illuminate\Http\Request;

final readonly class TenantResolver
{
    public function __construct(
        private TenantRepository $tenants,
    ) {
    }

    public function resolveFromRequest(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-Id');

        if ($tenantId === null || $tenantId === '') {
            return null;
        }

        return $this->tenants->findByPublicId($tenantId);
    }
}
