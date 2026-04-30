<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final readonly class TenantScope implements Scope
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {
    }

    /**
     * @param Builder<Model> $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->tenantContext->id();

        if ($tenantId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}
