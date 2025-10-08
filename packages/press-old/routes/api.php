<?php

use Illuminate\Support\Facades\Route;
use Moox\Press\Http\Controllers\WpUserController;

if (config('press.use_api')) {
    $entitiesConfig = config('press.entities.wp_users');
    foreach ($entitiesConfig as $entity => $config) {
        if ($config['enabled']) {
            $middleware = [];
            if (! $config['public']) {
                $middleware[] = $config['auth_type'] === 'platform' ? 'auth.platformtoken' : 'auth:sanctum';
            }

            Route::middleware($middleware)->group(function () use ($entity): void {
                Route::apiResource(sprintf('/%s/wpuser', $entity), WpUserController::class)->only(config('press.entities.wp_users.api.route_only'));
            });
        }
    }
}
