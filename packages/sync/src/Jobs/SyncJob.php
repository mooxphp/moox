<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Sync\Models\Sync;
use Moox\Sync\Services\SyncService;

class SyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle(SyncService $syncService)
    {
        try {
            $syncService->performSync($this->sync);
        } catch (\Exception $e) {
            $this->sync->update([
                'has_errors' => true,
                'error_message' => $e->getMessage(),
            ]);

            // TODO: implement retry logic here, if needed
        }
    }
}
