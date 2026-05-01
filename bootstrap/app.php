<?php

use App\Http\Middleware\EnsureTenantAccess;
use App\Http\Middleware\EnsureTokenHasScopes;
use App\Http\Middleware\AuditRateLimitAbuse;
use App\Http\Middleware\AdvancedRateLimit;
use App\Http\Middleware\RequestSecurityLock;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\RequireJsonRequest;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')->group(base_path('routes/oauth.php'));
        },
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['api', 'auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'oauth.scopes' => EnsureTokenHasScopes::class,
            'audit.rate_limit_abuse' => AuditRateLimitAbuse::class,
            'security.headers' => SecurityHeaders::class,
            'security.json' => RequireJsonRequest::class,
            'security.rate' => AdvancedRateLimit::class,
            'security.lock' => RequestSecurityLock::class,
            'tenant' => ResolveTenant::class,
            'tenant.access' => EnsureTenantAccess::class,
        ]);

        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            RequireJsonRequest::class,
        );

        $middleware->prependToPriorityList(
            RequireJsonRequest::class,
            SecurityHeaders::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
