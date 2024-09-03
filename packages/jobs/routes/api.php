<?php

use Moox\Jobs\HttpSharedHosting\QueueWorker;

if (config('core.shared_hosting.enabled')) {
    Route::get('/queue/work', QueueWorker::class);
}
