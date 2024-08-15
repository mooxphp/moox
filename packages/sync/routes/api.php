<?php

use Illuminate\Support\Facades\Route;

$models = config('sync.entities');
if(is_array($models)){
    foreach ($models as $entity => $config) {
        if($config['api']['enabled']){
            $middleware = [];

            if (! $config['api']['public']) {
                $middleware[] = $config['api']['auth_type'] === 'platform' ? 'auth.platformtoken' : 'auth:sanctum';
            }

            Route::middleware($middleware)->prefix('api')->group(function () use ($entity, $config) {
                Route::apiResource(Str::lower($entity),$config['api_controller'])->only($config['api']['active_routes']);
            });
        }
    }
}

Route::middleware('auth.platformtoken')->prefix('api')->group(function (){
    Route::get("platform/{id}/sync",[ \Moox\Sync\Http\Controllers\Api\PlatformSyncController::class, 'index']);
});

