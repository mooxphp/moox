<?php

namespace Moox\Sync\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;

class SyncService
{
    use LogLevel;

    protected $platformRelationService;

    public function __construct(PlatformRelationService $platformRelationService)
    {
        $this->platformRelationService = $platformRelationService;
    }

    public function shouldSyncModel($model, Platform $targetPlatform)
    {
        $this->logDebug('shouldSyncModel method entered', ['model' => $model, 'targetPlatform' => $targetPlatform]);

        $modelClass = get_class($model);

        // Check if the model is in the models_with_platform_relations config
        $modelsWithPlatformRelations = Config::get('sync.models_with_platform_relations', []);

        if (! in_array($modelClass, $modelsWithPlatformRelations)) {
            $this->logDebug('shouldSyncModel has no platform relations', ['model' => $modelClass, 'targetPlatform' => $targetPlatform]);

            return false;
        }

        if (class_exists($modelClass)) {
            $this->logDebug('shouldSyncModel method finished', ['model' => $model, 'targetPlatform' => $targetPlatform, 'result' => false]);

            return $this->platformRelationService->modelHasPlatform($model, $targetPlatform);
        } else {
            $this->logDebug('shouldSyncModel model class does not exist', ['model' => $modelClass, 'targetPlatform' => $targetPlatform]);
        }

        return false;
    }

    public function performSync(Sync $sync)
    {
        $sourcePlatform = $sync->sourcePlatform;
        $targetPlatform = $sync->targetPlatform;
        $sourceModel = $sync->source_model;

        $this->logDebug('performSync method entered', ['sync' => $sync]);

        // Check if the model should be synced
        if (! $this->shouldSyncModel(new $sourceModel, $targetPlatform)) {
            throw new \Exception("Model {$sourceModel} is not configured for platform relations or doesn't have a relation with the target platform.");
        }

        // Fetch data from source platform
        $sourceData = $this->fetchDataFromSource($sourcePlatform, $sourceModel);

        $this->logDebug('performSync fetched data from source', ['sync' => $sync, 'sourceData' => $sourceData]);

        // Transform data if needed
        $transformedData = $this->transformData($sourceData, $sync->field_mappings);

        $this->logDebug('performSync transformed data', ['sync' => $sync, 'transformedData' => $transformedData]);

        // Sync to target platform
        $this->syncToTarget($targetPlatform, $sync->target_model, $transformedData, $sync->if_exists);

        $this->logDebug('performSync synced data to target', ['sync' => $sync]);

        // Update last sync time
        $sync->update(['last_sync' => now()]);
    }

    protected function fetchDataFromSource(Platform $platform, string $model)
    {
        $response = Http::withToken($platform->api_token)
            ->get("{$platform->domain}/api/{$model}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to fetch data from source platform: '.$response->body());
    }

    protected function transformData(array $data, array $fieldMappings): array
    {
        $transformedData = [];
        foreach ($fieldMappings as $sourceField => $targetField) {
            $transformedData[$targetField] = $data[$sourceField] ?? null;
        }

        return $transformedData;
    }

    protected function syncToTarget(Platform $platform, string $model, array $data, string $ifExists)
    {
        $response = Http::withToken($platform->api_token)
            ->post("{$platform->domain}/api/{$model}", [
                'data' => $data,
                'if_exists' => $ifExists,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to sync data to target platform: '.$response->body());
        }

        return $response->json();
    }
}
