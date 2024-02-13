<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class BatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Bus::batch([
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
            new ShortJob(),
        ])->dispatch();

    }
}
