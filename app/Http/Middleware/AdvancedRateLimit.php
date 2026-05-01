<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\ApiSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdvancedRateLimit
{
    public function __construct(
        private ApiSecurityService $security,
    ) {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string $profile = 'ip'): Response
    {
        $result = $this->security->rateLimit($request, $profile);

        if (! $result['allowed']) {
            return response()
                ->json(['message' => 'Too many requests.'], Response::HTTP_TOO_MANY_REQUESTS)
                ->withHeaders([
                    'Retry-After' => (string) $result['retry_after'],
                    'X-RateLimit-Remaining' => '0',
                ]);
        }

        $response = $next($request);
        $response->headers->set('X-RateLimit-Remaining', (string) $result['remaining']);

        return $response;
    }
}
