<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Closure;
use RuntimeException;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function current(): Tenant
    {
        return $this->tenant ?? throw new RuntimeException('Tenant context has not been resolved.');
    }

    public function id(): string|int|null
    {
        return $this->tenant?->getKey();
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * @template TValue
     * @param Closure(): TValue $callback
     * @return TValue
     */
    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}
