<?php

use Illuminate\Support\Facades\Route;
use Moox\Press\Http\Controllers\WpUserController;


if (config('press.use_api')) {
    $entitiesConfig = config('press.entities.wp_users');
    foreach ($entitiesConfig as $entity => $config) {
        if ($config['enabled']) {
            $middleware = [];
            if (!$config['public']) {
                if ($config['auth_type'] === 'platform') {
                    $middleware[] = 'auth.platformtoken';
                } else {
                    $middleware[] = 'auth:sanctum';
                }
            }
            Route::middleware($middleware)->group(function () use ($entity, $config) {
                Route::apiResource("/$entity/wpuser", WpUserController::class)->only(config('press.entities.wp_users.api.route_only'));
            });
        }
    }
}
