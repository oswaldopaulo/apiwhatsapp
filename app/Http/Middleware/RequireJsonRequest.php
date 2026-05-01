<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireJsonRequest
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) config('api-security.json.require_accept', true) && ! $this->acceptsJson($request)) {
            return response()->json(['message' => 'The API only supports JSON responses.'], Response::HTTP_NOT_ACCEPTABLE);
        }

        if (
            (bool) config('api-security.json.require_content_type', true)
            && in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)
            && $request->getContent() !== ''
            && ! $request->isJson()
        ) {
            return response()->json(['message' => 'The request content type must be application/json.'], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        return $next($request);
    }

    private function acceptsJson(Request $request): bool
    {
        foreach ($request->getAcceptableContentTypes() as $contentType) {
            if ($contentType === '*/*' || $contentType === '*') {
                continue;
            }

            if ($contentType === 'application/json' || str_ends_with($contentType, '+json') || str_contains($contentType, '/json')) {
                return true;
            }
        }

        return false;
    }
}
