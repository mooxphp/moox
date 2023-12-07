<?php

use Adrolli\FilamentJobManager\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

// queue:work control features
//Route::middleware(['auth'])->group(function () {
Route::get('queue/status', [QueueController::class, 'queueStatus']);
Route::get('queue/start', [QueueController::class, 'startQueue']);
Route::get('queue/stop', [QueueController::class, 'stopQueue']);
//});
