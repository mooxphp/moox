<?php

use Illuminate\Support\Facades\Route;
use Moox\Jobs\Http\SharedHosting\QueueWorker;

if (config('core.shared_hosting.enabled')) {
    Route::get('/queue/work', QueueWorker::class);
}
