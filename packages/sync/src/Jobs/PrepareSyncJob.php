<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Core\Traits\LogLevel;

class PrepareSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $identifierField;

    protected $identifierValue;

    protected $modelClass;

    protected $eventType;

    protected $platformId;

    public function __construct($identifierField, $identifierValue, $modelClass, $eventType, $platformId)
    {
        $this->identifierField = $identifierField;
        $this->identifierValue = $identifierValue;
        $this->modelClass = $modelClass;
        $this->eventType = $eventType;
        $this->platformId = $platformId;
    }

    public function handle()
    {
        $this->logDebug('PrepareSyncJob started', [
            'identifier_field' => $this->identifierField,
            'identifier_value' => $this->identifierValue,
            'model_class' => $this->modelClass,
            'event_type' => $this->eventType,
            'platform_id' => $this->platformId,
        ]);

        try {
            $model = $this->findModel();
        } catch (ModelNotFoundException $e) {
            $this->handleModelNotFound();

            return;
        }

        // ... rest of the handle method ...
    }

    protected function findModel()
    {
        return $this->modelClass::where($this->identifierField, $this->identifierValue)->firstOrFail();
    }

    protected function handleModelNotFound()
    {
        $this->logDebug('Model not found, possibly deleted', [
            'identifier_field' => $this->identifierField,
            'identifier_value' => $this->identifierValue,
            'model_class' => $this->modelClass,
        ]);

        if ($this->eventType === 'deleted') {
            // If it's a delete event, we can proceed with sync
            $model = new $this->modelClass;
            $model->{$this->identifierField} = $this->identifierValue;
            // Proceed with sync logic for deleted model
        } else {
            // For other events, we can't proceed without the model
            $this->logDebug('Cannot proceed with sync for non-existent model');
        }
    }
}
