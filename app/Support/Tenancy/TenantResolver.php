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
        $tenantId = $this->resolveTenantIdentifier($request);

        if ($tenantId === null || $tenantId === '') {
            return null;
        }

        return $this->tenants->findByPublicId($tenantId);
    }

    private function resolveTenantIdentifier(Request $request): ?string
    {
        $header = (string) config('api-security.tenant.header', 'X-Tenant-ID');

        return $request->header($header);
    }
}
