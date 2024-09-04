<?php

namespace Moox\Sync\Services;

use Illuminate\Support\Facades\Config;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;

class SyncService
{
    use LogLevel;

    protected $platformRelationService;

    public function __construct(PlatformRelationService $platformRelationService)
    {
        $this->platformRelationService = $platformRelationService;
    }

    public function performSync($modelClass, array $modelData, string $eventType, Platform $sourcePlatform, ?Platform $targetPlatform = null)
    {
        $this->logDebug('performSync method entered', [
            'modelClass' => $modelClass,
            'eventType' => $eventType,
            'sourcePlatform' => $sourcePlatform->id,
            'targetPlatform' => $targetPlatform ? $targetPlatform->id : 'all',
        ]);

        if ($modelClass === Platform::class) {
            $this->syncPlatform($modelData, $eventType, $targetPlatform);
        } else {
            if ($targetPlatform) {
                $this->syncToSinglePlatform($modelClass, $modelData, $eventType, $sourcePlatform, $targetPlatform);
            } else {
                $this->syncToAllPlatforms($modelClass, $modelData, $eventType, $sourcePlatform);
            }
        }

        $this->logDebug('performSync method finished');
    }

    protected function syncPlatform(array $platformData, string $eventType, ?Platform $targetPlatform = null)
    {
        if ($targetPlatform) {
            $this->updatePlatform($platformData, $targetPlatform);
        } else {
            $allPlatforms = Platform::where('id', '!=', $platformData['id'])->get();
            foreach ($allPlatforms as $platform) {
                $this->updatePlatform($platformData, $platform);
            }
        }
    }

    protected function updatePlatform(array $platformData, Platform $targetPlatform)
    {
        $this->logDebug('Updating platform', ['platformId' => $platformData['id'], 'targetPlatform' => $targetPlatform->id]);

        $targetPlatform->update($platformData);

        $this->logDebug('Platform updated', ['platformId' => $platformData['id'], 'targetPlatform' => $targetPlatform->id]);
    }

    protected function syncToSinglePlatform($modelClass, array $modelData, string $eventType, Platform $sourcePlatform, Platform $targetPlatform)
    {
        if ($this->shouldSyncModel($modelClass, $targetPlatform)) {
            $this->processSyncEvent($modelClass, $modelData, $eventType, $targetPlatform);
        }
    }

    protected function syncToAllPlatforms($modelClass, array $modelData, string $eventType, Platform $sourcePlatform)
    {
        $targetPlatforms = Platform::where('id', '!=', $sourcePlatform->id)->get();

        foreach ($targetPlatforms as $targetPlatform) {
            if ($this->shouldSyncModel($modelClass, $targetPlatform)) {
                $this->processSyncEvent($modelClass, $modelData, $eventType, $targetPlatform);
            }
        }
    }

    protected function shouldSyncModel($modelClass, Platform $targetPlatform)
    {
        $modelsWithPlatformRelations = Config::get('sync.models_with_platform_relations', []);

        if (! in_array($modelClass, $modelsWithPlatformRelations)) {
            $this->logDebug('Model has no platform relations', ['model' => $modelClass, 'targetPlatform' => $targetPlatform->id]);

            return false;
        }

        // Additional logic can be added here if needed
        return true;
    }

    protected function processSyncEvent($modelClass, array $modelData, string $eventType, Platform $targetPlatform)
    {
        $this->logDebug('Processing sync event', [
            'modelClass' => $modelClass,
            'eventType' => $eventType,
            'targetPlatform' => $targetPlatform->id,
        ]);

        switch ($eventType) {
            case 'created':
                $this->createOrUpdateModel($modelClass, $modelData, $targetPlatform);
                break;
            case 'updated':
                $this->createOrUpdateModel($modelClass, $modelData, $targetPlatform);
                break;
            case 'deleted':
                $this->deleteModel($modelClass, $modelData, $targetPlatform);
                break;
            default:
                $this->logDebug('Unknown event type', ['eventType' => $eventType]);
        }
    }

    protected function createOrUpdateModel($modelClass, array $modelData, Platform $targetPlatform)
    {
        $model = $modelClass::updateOrCreate(
            ['id' => $modelData['id']],
            $modelData
        );

        $this->logDebug('Model created or updated', [
            'modelClass' => $modelClass,
            'modelId' => $model->id,
            'targetPlatform' => $targetPlatform->id,
        ]);

        if (method_exists($model, 'platforms')) {
            $this->platformRelationService->syncPlatformsForModel($model, [$targetPlatform->id]);
        }
    }

    protected function deleteModel($modelClass, array $modelData, Platform $targetPlatform)
    {
        $model = $modelClass::find($modelData['id']);

        if ($model) {
            $model->delete();
            $this->logDebug('Model deleted', [
                'modelClass' => $modelClass,
                'modelId' => $modelData['id'],
                'targetPlatform' => $targetPlatform->id,
            ]);
        } else {
            $this->logDebug('Model not found for deletion', [
                'modelClass' => $modelClass,
                'modelId' => $modelData['id'],
                'targetPlatform' => $targetPlatform->id,
            ]);
        }
    }
}
