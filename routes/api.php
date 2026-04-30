<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        ->middleware('oauth.scopes:messages:send');

    Route::get('/messages', fn () => response()->json(['data' => []]))
        ->middleware('oauth.scopes:messages:read');

    Route::post('/sessions', fn () => response()->json(['message' => 'Session operation accepted.'], 202))
        ->middleware('oauth.scopes:sessions:manage');

    Route::get('/stats', fn () => response()->json(['data' => []]))
        ->middleware('oauth.scopes:stats:read');

    Route::post('/webhooks', fn () => response()->json(['message' => 'Webhook configuration accepted.'], 202))
        ->middleware('oauth.scopes:webhooks:manage');

    Route::get('/config', fn () => response()->json(['data' => []]))
        ->middleware('oauth.scopes:config:read');

    Route::put('/config', fn () => response()->json(['message' => 'Configuration update accepted.']))
        ->middleware('oauth.scopes:config:write');
});
