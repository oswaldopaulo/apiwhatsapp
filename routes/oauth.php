<?php

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\ScopeController;
use Laravel\Passport\Http\Controllers\TransientTokenController;

Route::prefix('oauth')->group(function (): void {
    Route::post('/token', [AccessTokenController::class, 'issueToken'])
        ->name('passport.token');

    Route::post('/token/refresh', [TransientTokenController::class, 'refresh'])
        ->name('passport.token.refresh');

    Route::get('/scopes', [ScopeController::class, 'all'])
        ->name('passport.scopes.index');
});
