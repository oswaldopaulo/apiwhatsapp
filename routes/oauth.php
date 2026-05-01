<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuth\AuditedAccessTokenController;
use App\Http\Controllers\OAuth\AuditedAuthorizedAccessTokenController;
use Laravel\Passport\Http\Controllers\ScopeController;
use Laravel\Passport\Http\Controllers\TransientTokenController;

Route::middleware(['security.headers', 'security.json', 'security.rate:ip'])->prefix('oauth')->group(function (): void {
    Route::post('/token', [AuditedAccessTokenController::class, 'issueToken'])
        ->name('passport.token');

    Route::post('/token/refresh', [TransientTokenController::class, 'refresh'])
        ->name('passport.token.refresh');

    Route::delete('/tokens/{token_id}', [AuditedAuthorizedAccessTokenController::class, 'destroy'])
        ->middleware('auth:api')
        ->name('passport.tokens.destroy');

    Route::get('/scopes', [ScopeController::class, 'all'])
        ->name('passport.scopes.index');
});
