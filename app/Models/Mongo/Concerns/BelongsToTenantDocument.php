<?php

declare(strict_types=1);

namespace App\Models\Mongo\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenantDocument
{
    public static function bootBelongsToTenantDocument(): void
    {
        static::creating(static fn (object $model): mixed => self::ensureTenantIsCurrent($model));
        static::updating(static fn (object $model): mixed => self::ensureTenantIsCurrent($model));
    }

    /**
     * @param Builder<static> $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, string|int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * @throws AuthorizationException
     */
    private static function ensureTenantIsCurrent(object $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            return;
        }

        if (empty($model->tenant_id)) {
            $model->tenant_id = $tenantId;

            return;
        }

        if ((string) $model->tenant_id !== (string) $tenantId) {
            throw new AuthorizationException('The document does not belong to the current tenant.');
        }
    }
}
