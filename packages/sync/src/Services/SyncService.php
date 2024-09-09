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
        if ($this->shouldSyncModel($modelClass, $modelData, $targetPlatform, $this->platformRelationService->getSync())) {
            $this->processSyncEvent($modelClass, $modelData, $eventType, $targetPlatform, $this->platformRelationService->getSync());
        }
    }

    protected function syncToAllPlatforms($modelClass, array $modelData, string $eventType, Platform $sourcePlatform)
    {
        $targetPlatforms = Platform::where('id', '!=', $sourcePlatform->id)->get();

        foreach ($targetPlatforms as $targetPlatform) {
            if ($this->shouldSyncModel($modelClass, $modelData, $targetPlatform, $this->platformRelationService->getSync())) {
                $this->processSyncEvent($modelClass, $modelData, $eventType, $targetPlatform, $this->platformRelationService->getSync());
            }
        }
    }

    protected function shouldSyncModel($modelClass, array $modelData, Platform $targetPlatform, $sync)
    {
        if (! $sync->use_platform_relations) {
            $this->logDebug('Platform relations not used for this sync', ['model' => $modelClass, 'targetPlatform' => $targetPlatform->id]);

            return true;
        }

        $modelsWithPlatformRelations = Config::get('sync.models_with_platform_relations', []);

        if (! in_array($modelClass, $modelsWithPlatformRelations)) {
            $this->logDebug('Model has no platform relations', ['model' => $modelClass, 'targetPlatform' => $targetPlatform->id]);

            return false;
        }

        $modelId = $this->getModelId($modelData);
        $hasRelation = $this->platformRelationService->checkPlatformRelationForModel($modelClass, $modelId, $targetPlatform->id);

        $this->logDebug('Checking platform relation', [
            'model' => $modelClass,
            'modelId' => $modelId,
            'targetPlatform' => $targetPlatform->id,
            'hasRelation' => $hasRelation,
        ]);

        return $hasRelation;
    }

    protected function getModelId(array $modelData)
    {
        $identifierFields = Config::get('sync.local_identifier_fields', ['id', 'uuid', 'ulid']);

        foreach ($identifierFields as $field) {
            if (isset($modelData[$field])) {
                return $modelData[$field];
            }
        }

        throw new \Exception('Unable to determine model identifier');
    }

    protected function processSyncEvent($modelClass, array $modelData, string $eventType, Platform $targetPlatform, $sync)
    {
        $this->logDebug('Processing sync event', [
            'modelClass' => $modelClass,
            'eventType' => $eventType,
            'targetPlatform' => $targetPlatform->id,
        ]);

        if ($this->shouldSyncModel($modelClass, $modelData, $targetPlatform, $sync)) {
            switch ($eventType) {
                case 'created':
                case 'updated':
                    $this->createOrUpdateModel($modelClass, $modelData, $targetPlatform);
                    break;
                case 'deleted':
                    $this->deleteModel($modelClass, $modelData, $targetPlatform);
                    break;
                default:
                    $this->logDebug('Unknown event type', ['eventType' => $eventType]);
            }
        } elseif ($sync->use_platform_relations && $eventType !== 'deleted') {
            // If the model should not be synced but exists on the target, delete it
            $this->deleteModelIfExists($modelClass, $modelData, $targetPlatform);
        }
    }

    protected function deleteModelIfExists($modelClass, array $modelData, Platform $targetPlatform)
    {
        $modelId = $this->getModelId($modelData);
        $existingModel = $modelClass::where($this->getModelId($modelData), $modelId)->first();

        if ($existingModel) {
            $this->logDebug('Deleting model that no longer has relation to target platform', [
                'model' => $modelClass,
                'modelId' => $modelId,
                'targetPlatform' => $targetPlatform->id,
            ]);
            $this->deleteModel($modelClass, $modelData, $targetPlatform);
        }
    }

    protected function createOrUpdateModel($modelClass, array $modelData, Platform $targetPlatform)
    {
        $uniqueFields = config('sync.unique_identifier_fields', ['ulid', 'uuid', 'slug', 'name', 'title']);
        $uniqueIdentifier = null;

        foreach ($uniqueFields as $field) {
            if (isset($modelData[$field])) {
                $uniqueIdentifier = $field;
                break;
            }
        }

        if (! $uniqueIdentifier) {
            throw new \Exception("No unique identifier found for model {$modelClass}");
        }

        $model = $modelClass::updateOrCreate(
            [$uniqueIdentifier => $modelData[$uniqueIdentifier]],
            $modelData
        );

        $this->logDebug('Model created or updated', [
            'modelClass' => $modelClass,
            'modelId' => $model->id,
            'uniqueIdentifier' => $uniqueIdentifier,
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
