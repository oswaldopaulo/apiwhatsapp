<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTokenHasScopes
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->jsonError('Unauthenticated.', Response::HTTP_UNAUTHORIZED);
        }

        foreach ($scopes as $scope) {
            if (! $user->tokenCan($scope)) {
                return $this->jsonError('The access token is missing the required scope.', Response::HTTP_FORBIDDEN, [
                    'required_scope' => $scope,
                ]);
            }
        }

        return $next($request);
    }

    /**
     * @param array<string, string> $extra
     */
    private function jsonError(string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            ...$extra,
        ], $status);
    }
}
