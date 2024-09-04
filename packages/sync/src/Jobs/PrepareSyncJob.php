<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;

class PrepareSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $modelId;

    protected $modelClass;

    protected $eventType;

    protected $platformId;

    public function __construct($modelId, $modelClass, $eventType, $platformId)
    {
        $this->modelId = $modelId;
        $this->modelClass = $modelClass;
        $this->eventType = $eventType;
        $this->platformId = $platformId;
    }

    public function handle()
    {
        $this->logDebug('PrepareSyncJob started', [
            'model_id' => $this->modelId,
            'model_class' => $this->modelClass,
            'event_type' => $this->eventType,
            'platform_id' => $this->platformId,
        ]);

        try {
            $model = $this->modelClass::findOrFail($this->modelId);
        } catch (ModelNotFoundException $e) {
            $this->logDebug('Model not found, possibly deleted', [
                'model_id' => $this->modelId,
                'model_class' => $this->modelClass,
            ]);

            if ($this->eventType === 'deleted') {
                // If it's a delete event, we can proceed with sync
                $model = new $this->modelClass;
                $model->ID = $this->modelId;
            } else {
                // For other events, we can't proceed without the model
                return;
            }
        }

        try {
            $platform = Platform::findOrFail($this->platformId);
        } catch (ModelNotFoundException $e) {
            $this->logDebug('Platform not found', ['platform_id' => $this->platformId]);

            return;
        }

        $modelData = $model->toArray();

        if ($model instanceof \Moox\Press\Models\WpUser && $this->eventType !== 'deleted') {
            $userMeta = $model->getAllMetaAttributes();
            $this->logDebug('User meta data retrieved in deferred job', ['user_meta' => $userMeta]);
            $modelData = array_merge($modelData, $userMeta);
        }

        $syncData = [
            'event_type' => $this->eventType,
            'model' => $modelData,
            'model_class' => $this->modelClass,
            'platform' => $platform->toArray(),
        ];

        $this->logDebug('Sync data prepared in deferred job', ['sync_data' => $syncData]);

        $this->invokeWebhooks($syncData);
    }

    protected function invokeWebhooks(array $data)
    {
        // Implement the webhook invocation logic here
        // This might involve sending HTTP requests to other platforms
        // Make sure to log the process and handle any errors
    }
}
