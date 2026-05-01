<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Audit\AuditService;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureTenantAccess
{
    public function __construct(
        private TenantContext $tenantContext,
        private AuditService $audit,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantContext->get();
        $user = $request->user();

        if ($tenant === null) {
            return response()->json(['message' => 'Tenant not resolved.'], Response::HTTP_NOT_FOUND);
        }

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $isOwner = $tenant->owner_user_id !== null && (string) $tenant->owner_user_id === (string) $user->getKey();
        $isMember = $user->tenants()->whereKey($tenant->getKey())->exists();

        if (! $isOwner && ! $isMember) {
            $this->audit->record('tenant.cross_access_attempt', 'blocked', [
                'requested_tenant_id' => $tenant->getKey(),
            ], $tenant, $user, $request);

            return response()->json(['message' => 'This token cannot access the requested tenant.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
