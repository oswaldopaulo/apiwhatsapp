<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\ApiSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequestSecurityLock
{
    public function __construct(
        private ApiSecurityService $security,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string $scope = 'ip'): Response
    {
        return $this->security->withRequestLock($request, $scope, fn (): Response => $next($request));
    }
}
