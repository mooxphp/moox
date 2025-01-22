<?php

use Illuminate\Support\Facades\Route;
use Moox\Expiry\Http\Controllers\Api\ExpiryController;

if (config('expiry.api')) {
    Route::prefix('/api/expiries')->group(function (): void {
        Route::get('count', [ExpiryController::class, 'count']);
        Route::get('count/user/{user}', [ExpiryController::class, 'countForUser']);
        Route::apiResource('/', ExpiryController::class);
    });
}
