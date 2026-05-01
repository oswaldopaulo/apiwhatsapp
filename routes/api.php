<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TenantConfigurationController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhatsAppSessionController;

Route::middleware(['security.headers', 'security.json', 'security.rate:ip'])->group(function (): void {
    Route::post('/v1/webhooks/whatsapp', [WebhookController::class, 'whatsapp'])
        ->middleware(['security.rate:webhook', 'security.lock:tenant']);

    Route::middleware(['auth:api', 'tenant', 'tenant.access'])->group(function (): void {
        Route::get('/me', function (Request $request) {
            $user = $request->user();
            $tenant = app(\App\Support\Tenancy\TenantContext::class)->current();

            return response()->json([
                'data' => [
                    'id' => $user?->getKey(),
                    'name' => $user?->name,
                    'email' => $user?->email,
                    'tenant' => [
                        'id' => $tenant->getKey(),
                        'public_id' => $tenant->public_id,
                        'name' => $tenant->name,
                    ],
                ],
            ]);
        });

        Route::post('/messages', fn () => response()->json(['message' => 'Message accepted for queueing.'], 202))
            ->middleware(['oauth.scopes:messages:send', 'can:send messages']);

        Route::get('/messages', fn () => response()->json(['data' => []]))
            ->middleware(['oauth.scopes:messages:read', 'can:read messages']);

        Route::post('/sessions', fn () => response()->json(['message' => 'Session operation accepted.'], 202))
            ->middleware(['oauth.scopes:sessions:manage', 'can:manage sessions']);

        Route::get('/stats', fn () => response()->json(['data' => []]))
            ->middleware(['oauth.scopes:stats:read', 'can:read stats']);

        Route::post('/webhooks', fn () => response()->json(['message' => 'Webhook configuration accepted.'], 202))
            ->middleware(['oauth.scopes:webhooks:manage', 'can:manage webhooks']);

        Route::get('/config', [TenantConfigurationController::class, 'show'])
            ->middleware(['oauth.scopes:config:read', 'can:manage config']);

        Route::put('/config', [TenantConfigurationController::class, 'update'])
            ->middleware(['oauth.scopes:config:write', 'can:manage config']);

        Route::prefix('v1')->group(function (): void {
            Route::post('/messages/send', [MessageController::class, 'send'])
                ->middleware([
                    'oauth.scopes:messages:send',
                    'can:send messages',
                    'security.rate:sensitive',
                    'security.rate:tenant',
                    'security.rate:session',
                    'security.lock:session',
                ]);

            Route::get('/sessions', [WhatsAppSessionController::class, 'index'])
                ->middleware(['oauth.scopes:sessions:manage', 'can:manage sessions', 'security.rate:tenant']);

            Route::post('/sessions', [WhatsAppSessionController::class, 'store'])
                ->middleware(['oauth.scopes:sessions:manage', 'can:manage sessions', 'security.rate:sensitive', 'security.rate:tenant', 'security.lock:tenant']);

            Route::get('/sessions/{id}', [WhatsAppSessionController::class, 'show'])
                ->middleware(['oauth.scopes:sessions:manage', 'can:manage sessions', 'security.rate:tenant']);

            Route::delete('/sessions/{id}', [WhatsAppSessionController::class, 'destroy'])
                ->middleware(['oauth.scopes:sessions:manage', 'can:manage sessions', 'security.rate:sensitive', 'security.rate:tenant', 'security.lock:tenant']);

            Route::get('/stats/messages/hour', [StatsController::class, 'messagesHour'])
                ->middleware(['oauth.scopes:stats:read', 'can:read stats']);

            Route::get('/stats/messages/day', [StatsController::class, 'messagesDay'])
                ->middleware(['oauth.scopes:stats:read', 'can:read stats']);

            Route::get('/stats/errors', [StatsController::class, 'errors'])
                ->middleware(['oauth.scopes:stats:read', 'can:read stats']);

            Route::get('/stats/queue', [StatsController::class, 'queue'])
                ->middleware(['oauth.scopes:stats:read', 'can:read stats']);

            Route::get('/stats/delivery-rate', [StatsController::class, 'deliveryRate'])
                ->middleware(['oauth.scopes:stats:read', 'can:read stats']);

            Route::get('/stats/sessions', [StatsController::class, 'sessions'])
                ->middleware(['oauth.scopes:stats:read', 'can:read stats']);

            Route::get('/config', [TenantConfigurationController::class, 'show'])
                ->middleware(['oauth.scopes:config:read', 'can:manage config', 'security.rate:tenant']);

            Route::put('/config', [TenantConfigurationController::class, 'update'])
                ->middleware(['oauth.scopes:config:write', 'can:manage config', 'security.rate:sensitive', 'security.rate:tenant', 'security.lock:tenant']);
        });
    });
});
