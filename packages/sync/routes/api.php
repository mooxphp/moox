<?php

use Illuminate\Support\Facades\Route;

if (config('sync.use_api') && config('sync.api_entities')) {
    foreach (config('sync.api_entities') as $entity => $config) {
        if ($config['enabled']) {
            $middleware = [];

            if (! $config['public']) {
                $middleware[] = $config['auth_type'] === 'platform' ? 'auth.platformtoken' : 'auth:sanctum';
            }

            Route::middleware($middleware)->prefix('api')->group(function () use ($entity, $config) {
                Route::apiResource("$entity", $config['controller_class'])->only($config['route_only']);
            });
        }
    }
}
