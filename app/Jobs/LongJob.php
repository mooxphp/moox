<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Jobs\Traits\JobProgress;

class LongJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    public function __construct()
    {
        $this->tries = 10;
        $this->timeout = 1200;
        $this->maxExceptions = 3;
        $this->backoff = 2400;
    }

    public function handle()
    {
        $count = 0;
        $steps = 1;
        $final = 100;

        while ($count < $final) {
            $this->setProgress($count);
            $count = $count + $steps;
            sleep(20);
        }
    }
}
