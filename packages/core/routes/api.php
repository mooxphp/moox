<?php

use Illuminate\Support\Facades\Route;
use Moox\Core\Http\SharedHosting\Scheduler;
use Moox\Core\Http\Controllers\Api\CoreController;
use Moox\Core\Http\Controllers\Api\ModelController;

Route::get('api/core', CoreController::class.'@index');
Route::get('api/models', ModelController::class.'@index');

if (config('core.shared_hosting.enabled')) {
    Route::get('/schedule/run', Scheduler::class);
}
