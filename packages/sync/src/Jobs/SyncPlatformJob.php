<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Sync\Models\Sync;

class SyncPlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $platform;

    public function __construct($platform)
    {
        $this->platform = $platform;
    }

    public function handle()
    {
        $platform = $this->platform;

        $platform->syncs()->each(function (Sync $sync) use ($platform) {
            $sync->run($platform);
        });
    }
}
