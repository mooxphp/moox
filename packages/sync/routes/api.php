<?php

use Illuminate\Support\Facades\Route;
use Moox\Sync\Http\Controllers\PlatformController;
use Moox\Sync\Http\Controllers\SyncController;

if (config('sync.use_api') && config('sync.api_entities')) {
    foreach (config('sync.api_entities') as $entity => $config) {
        if ($config['enabled']) {
            $middleware = [];

            if (!$config['public']) {
                $middleware[] = $config['auth_type'] === 'platform' ? 'auth.platformtoken' : 'auth:sanctum';
            }

            Route::group(['middleware' => $middleware], function () use ($entity, $config) {
                Route::resource("api/$entity", $config['controller_class'])->only($config['route_only']);

                if (isset($config['nested'])) {
                    foreach ($config['nested'] as $nestedEntity => $nestedConfig) {
                        Route::resource("api/", $nestedConfig['controller_class'])
                            ->only($nestedConfig['route_only']);
                    }
                }

                    Route::get('/', [PlatformController::class, 'index']);
                    Route::get('/{platform}', [PlatformController::class, 'show']);

                    Route::prefix('/{platform}/syncs')->group(function () {
                        Route::get('/', [SyncController::class, 'index']);
                        Route::get('/{sync}', [SyncController::class, 'show']);
                        Route::post('/', [SyncController::class, 'store']);
                        Route::put('/{sync}', [SyncController::class, 'update']);
                        Route::delete('/{sync}', [SyncController::class, 'destroy']);
                    });
            });
        }
    }
}
