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

    protected $localIdentifier;

    protected $modelClass;

    protected $eventType;

    protected $platformId;

    public function __construct($localIdentifier, $modelClass, $eventType, $platformId)
    {
        $this->localIdentifier = $localIdentifier;
        $this->modelClass = $modelClass;
        $this->eventType = $eventType;
        $this->platformId = $platformId;
    }

    public function handle()
    {
        $this->logDebug('PrepareSyncJob started', [
            'local_identifier' => $this->localIdentifier,
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
        $localIdentifierFields = config('sync.local_identifier_fields', ['id']);
        $query = $this->modelClass::query();

        foreach ($localIdentifierFields as $field) {
            $query->orWhere($field, $this->localIdentifier);
        }

        return $query->firstOrFail();
    }

    protected function handleModelNotFound()
    {
        $this->logDebug('Model not found, possibly deleted', [
            'local_identifier' => $this->localIdentifier,
            'model_class' => $this->modelClass,
        ]);

        if ($this->eventType === 'deleted') {
            // If it's a delete event, we can proceed with sync
            $model = new $this->modelClass;
            $model->{$this->getFirstLocalIdentifierField()} = $this->localIdentifier;
            // Proceed with sync logic for deleted model
        } else {
            // For other events, we can't proceed without the model
            $this->logDebug('Cannot proceed with sync for non-existent model');
        }
    }

    protected function getFirstLocalIdentifierField()
    {
        return config('sync.local_identifier_fields.0', 'id');
    }
}
