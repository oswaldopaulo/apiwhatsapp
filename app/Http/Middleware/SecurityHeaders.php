<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = (array) config('api-security.headers', []);

        $response->headers->set('X-Content-Type-Options', (string) ($headers['content_type_options'] ?? 'nosniff'));
        $response->headers->set('X-Frame-Options', (string) ($headers['frame_options'] ?? 'DENY'));
        $response->headers->set('Referrer-Policy', (string) ($headers['referrer_policy'] ?? 'no-referrer'));
        $response->headers->set('X-XSS-Protection', (string) ($headers['xss_protection'] ?? '0'));
        $response->headers->set('Permissions-Policy', (string) ($headers['permissions_policy'] ?? 'camera=(), microphone=(), geolocation=()'));
        $response->headers->set('Content-Security-Policy', (string) ($headers['content_security_policy'] ?? "default-src 'none'"));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', (string) ($headers['hsts'] ?? 'max-age=31536000; includeSubDomains'));
        }

        if (($headers['powered_by'] ?? false) === false) {
            $response->headers->remove('X-Powered-By');
        }

        return $response;
    }
}
