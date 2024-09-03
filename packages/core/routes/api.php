<?php

use Moox\Core\Http\SharedHosting\Scheduler;

Route::get('api/core', \Moox\Core\Http\Controllers\Api\CoreController::class.'@index');
Route::get('api/models', \Moox\Core\Http\Controllers\Api\ModelController::class.'@index');

if (config('core.shared_hosting.enabled')) {
    Route::get('/schedule/run', Scheduler::class);
}
