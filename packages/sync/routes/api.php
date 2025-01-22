<?php

use Illuminate\Support\Facades\Route;
use Moox\Sync\Http\Controllers\Api\PlatformSyncController;
use Moox\Sync\Http\Controllers\SyncResponseController;
use Moox\Sync\Http\Controllers\SyncWebhookController;

$models = config('sync.entities');
if (is_array($models)) {
    foreach ($models as $entity => $config) {
        if ($config['api']['enabled']) {
            $middleware = [];

            if (! $config['api']['public']) {
                $middleware[] = $config['api']['auth_type'] === 'platform' ? 'auth.platformtoken' : 'auth:sanctum';
            }

            Route::middleware($middleware)->prefix('api')->group(function () use ($entity, $config): void {
                Route::apiResource(Str::lower($entity), $config['api_controller'])->only($config['api']['active_routes']);
            });
        }
    }
}

Route::middleware('auth.platformtoken')->prefix('api')->group(function (): void {
    Route::get('platform/{id}/sync', [PlatformSyncController::class, 'index']);
});

$webhookPath = config('sync.sync_webhook_url', '/sync-webhook');
Route::post($webhookPath, [SyncWebhookController::class, 'handle'])
    ->middleware('webhook.auth');

$responsePath = config('sync.sync_response_url', '/sync-response');
Route::post($responsePath, [SyncResponseController::class, 'sync'])
    ->middleware('sync.response.auth');
