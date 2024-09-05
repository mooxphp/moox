<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Handlers\PressSyncHandler;
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

    protected function getModelId()
    {
        $idFields = config('sync.local_identifier_fields', ['ID', 'uuid', 'ulid', 'id']);
        foreach ($idFields as $field) {
            if (isset($this->modelData[$field])) {
                return [
                    'field' => $field,
                    'value' => $this->modelData[$field],
                ];
            }
        }
        throw new \Exception('No suitable ID field found for model');
    }

    public function handle()
    {
        try {
            $modelId = $this->getModelId();
            $this->logDebug('Syncing model', [
                'model_class' => $this->modelClass,
                'model_id_field' => $modelId['field'],
                'model_id_value' => $modelId['value'],
                'event_type' => $this->eventType,
            ]);

            if ($this->modelClass === Platform::class) {
                $this->syncPlatform();
            } else {
                $this->syncModel($modelId);
            }
        } catch (\Exception $e) {
            $this->logDebug('Error syncing model', [
                'model_class' => $this->modelClass,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function syncPlatform()
    {
        $platform = Platform::updateOrCreate(
            ['name' => $this->modelData['name']],
            $this->modelData
        );

        $this->logDebug('Platform synced successfully', [
            'platform_id' => $platform->id,
            'platform_name' => $platform->name,
        ]);
    }

    protected function syncModel($modelId)
    {
        if ($this->eventType === 'deleted') {
            $model = $this->modelClass::where($modelId['field'], $modelId['value'])->first();
            if ($model) {
                $model->delete();
                $this->logDebug('Model deleted successfully', [
                    'model_class' => $this->modelClass,
                    'model_id_field' => $modelId['field'],
                    'model_id_value' => $modelId['value'],
                ]);
            }
        } else {
            if ($this->isPressSyncableModel()) {
                $handler = new PressSyncHandler($this->modelClass, $this->modelData);
                $model = $handler->sync();
            } else {
                $model = $this->modelClass::updateOrCreate(
                    [$modelId['field'] => $modelId['value']],
                    $this->modelData
                );
            }

            $this->logDebug('Model synced successfully', [
                'model_class' => $this->modelClass,
                'model_id_field' => $modelId['field'],
                'model_id_value' => $modelId['value'],
            ]);
        }
    }

    protected function isPressSyncableModel(): bool
    {
        return in_array($this->modelClass, [
            \Moox\Press\Models\WpUser::class,
            \Moox\Press\Models\WpPost::class,
            // Add other Press models as needed
        ]);
    }
}
