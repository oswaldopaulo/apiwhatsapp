<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;

trait TenantAwareJob
{
    public function tenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * @template TValue
     * @param Closure(): TValue $callback
     * @return TValue
     */
    public function withTenantContext(Closure $callback): mixed
    {
        $tenant = Tenant::query()->findOrFail($this->tenantId);

        return app(TenantContext::class)->run($tenant, $callback);
    }
}
