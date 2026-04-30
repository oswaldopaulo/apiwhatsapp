<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

trait TenantAwareJob
{
    public function tenantId(): string
    {
        return $this->tenantId;
    }
}
