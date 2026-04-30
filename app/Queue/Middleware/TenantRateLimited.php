<?php

declare(strict_types=1);

namespace App\Queue\Middleware;

use Closure;

final readonly class TenantRateLimited
{
    public function __construct(
        private string|int $tenantId,
    ) {
    }

    /**
     * @param object $job
     * @param Closure(object): mixed $next
     */
    public function handle(object $job, Closure $next): mixed
    {
        return $next($job);
    }

    public function tenantId(): string|int
    {
        return $this->tenantId;
    }
}
