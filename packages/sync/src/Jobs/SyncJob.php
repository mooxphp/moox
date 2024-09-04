<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;

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

    public function handle()
    {
        $this->logDebug('SyncJob handle method entered', [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelData['id'],
            'event_type' => $this->eventType,
            'source_platform' => $this->sourcePlatform->id,
        ]);

        try {
            $model = $this->modelClass::updateOrCreate(
                ['id' => $this->modelData['id']],
                $this->modelData
            );

            $this->logDebug('Model synced successfully', [
                'model_class' => $this->modelClass,
                'model_id' => $model->id,
            ]);
        } catch (\Exception $e) {
            $this->logDebug('Error syncing model', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelData['id'],
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
