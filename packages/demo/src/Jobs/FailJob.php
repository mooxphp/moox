<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Jobs\Traits\JobProgress;

class FailJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public $tries = 10;

    /**
     * @var int
     */
    public $timeout = 10;

    /**
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * @var int
     */
    public $backoff = 20;

    public function handle(): never
    {
        throw new Exception('This job is meant to fail.');
    }
}
