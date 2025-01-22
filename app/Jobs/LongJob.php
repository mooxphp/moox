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
    public $timeout = 1200;

    /**
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * @var int
     */
    public $backoff = 2400;

    public function handle(): void
    {
        $count = 0;
        $steps = 1;
        $final = 100;

        while ($count < $final) {
            $this->setProgress($count);
            $count += $steps;
            sleep(20);
        }
    }
}
