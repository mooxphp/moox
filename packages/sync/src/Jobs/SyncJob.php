<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;
use Moox\Sync\Services\SyncService;

class SyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $modelClass;

    protected $modelData;

    protected $eventType;

    protected $sourcePlatform;

    public function __construct($modelClass, $modelData, $eventType, Platform $sourcePlatform)
    {
        $this->modelClass = $modelClass;
        $this->modelData = $modelData;
        $this->eventType = $eventType;
        $this->sourcePlatform = $sourcePlatform;
    }

    public function handle(SyncService $syncService)
    {
        try {
            $this->logDebug('SyncJob handle method entered', [
                'modelClass' => $this->modelClass,
                'eventType' => $this->eventType,
                'sourcePlatform' => $this->sourcePlatform->id,
            ]);

            $syncService->performSync($this->modelClass, $this->modelData, $this->eventType, $this->sourcePlatform);

            $this->logDebug('SyncJob handle method finished successfully');

        } catch (\Exception $e) {
            $this->logDebug('SyncJob encountered an error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
