<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Moox\UserDevice\Http\Controllers\TrustDeviceController;

Route::middleware(['web', 'signed'])
    ->name('user-device.')
    ->group(function (): void {
        Route::get('/user-device/{panel}/devices/{device}/trust', TrustDeviceController::class)
            ->where('panel', '[A-Za-z0-9\\-_]+')
            ->whereNumber('device')
            ->name('devices.trust');
    });
