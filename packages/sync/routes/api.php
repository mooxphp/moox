<?php

use Illuminate\Support\Facades\Route;
use Moox\Sync\Http\Controllers\SyncWebhookController;

$models = config('sync.entities');
if (is_array($models)) {
    foreach ($models as $entity => $config) {
        if ($config['api']['enabled']) {
            $middleware = [];

            if (! $config['api']['public']) {
                $middleware[] = $config['api']['auth_type'] === 'platform' ? 'auth.platformtoken' : 'auth:sanctum';
            }

            Route::middleware($middleware)->prefix('api')->group(function () use ($entity, $config) {
                Route::apiResource(Str::lower($entity), $config['api_controller'])->only($config['api']['active_routes']);
            });
        }
    }
}

Route::middleware('auth.platformtoken')->prefix('api')->group(function () {
    Route::get('platform/{id}/sync', [\Moox\Sync\Http\Controllers\Api\PlatformSyncController::class, 'index']);
});

Route::post('/sync-webhook', function (\Illuminate\Http\Request $request) {
    \Log::info('Sync webhook route hit');
    $controller = app()->make(SyncWebhookController::class);
    \Log::info('SyncWebhookController instantiated');

    return $controller->handle($request);
})->withoutMiddleware('*');
