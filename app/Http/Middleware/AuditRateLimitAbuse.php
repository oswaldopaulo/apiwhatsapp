<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Audit\AuditService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuditRateLimitAbuse
{
    public function __construct(
        private AuditService $audit,
        private TenantContext $tenantContext,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            $this->audit->record('rate_limit.abuse', 'blocked', [
                'route' => optional($request->route())->getName() ?? $request->path(),
            ], $this->tenantContext->get(), $request->user(), $request);
        }

        return $response;
    }
}
