<?php

namespace Moox\Sync\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;

class SyncService
{
    protected $platformRelationService;

    public function __construct(PlatformRelationService $platformRelationService)
    {
        $this->platformRelationService = $platformRelationService;
    }

    public function shouldSyncModel($model, Platform $targetPlatform)
    {
        $modelClass = get_class($model);

        // Check if the model is in the models_with_platform_relations config
        $modelsWithPlatformRelations = Config::get('sync.models_with_platform_relations', []);

        if (! in_array($modelClass, $modelsWithPlatformRelations)) {
            return false;
        }

        if (class_exists($modelClass)) {
            return $this->platformRelationService->modelHasPlatform($model, $targetPlatform);
        }

        return false;
    }

    public function performSync(Sync $sync)
    {
        $sourcePlatform = $sync->sourcePlatform;
        $targetPlatform = $sync->targetPlatform;
        $sourceModel = $sync->source_model;

        // Check if the model should be synced
        if (! $this->shouldSyncModel(new $sourceModel, $targetPlatform)) {
            throw new \Exception("Model {$sourceModel} is not configured for platform relations or doesn't have a relation with the target platform.");
        }

        // Fetch data from source platform
        $sourceData = $this->fetchDataFromSource($sourcePlatform, $sourceModel);

        // Transform data if needed
        $transformedData = $this->transformData($sourceData, $sync->field_mappings);

        // Sync to target platform
        $this->syncToTarget($targetPlatform, $sync->target_model, $transformedData, $sync->if_exists);

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
