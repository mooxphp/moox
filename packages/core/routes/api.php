<?php

use Moox\Core\Http\Controllers\Api\CoreController;
use Moox\Core\Http\Controllers\Api\ModelController;
use Moox\Core\Http\SharedHosting\Scheduler;

Route::get('api/core', CoreController::class.'@index');
Route::get('api/models', ModelController::class.'@index');

if (config('core.shared_hosting.enabled')) {
    Route::get('/schedule/run', Scheduler::class);
}
