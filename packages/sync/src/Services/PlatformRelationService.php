<?php

namespace Moox\Sync\Services;

use Illuminate\Support\Facades\DB;
use Moox\Sync\Models\Platform;

class PlatformRelationService
{
    public function syncPlatformsForModel($model, array $platformIds): void
    {
        $modelType = get_class($model);
        $modelId = $model->getKey();

        DB::table('model_platform')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->delete();

        $insertData = array_map(function ($platformId) use ($modelType, $modelId) {
            return [
                'model_type' => $modelType,
                'model_id' => $modelId,
                'platform_id' => $platformId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $platformIds);

        DB::table('model_platform')->insert($insertData);
    }

    public function getPlatformsForModel($modelClassOrInstance, $modelId = null)
    {
        if (is_object($modelClassOrInstance)) {
            $modelClass = get_class($modelClassOrInstance);
            $modelId = $modelClassOrInstance->getKey();
        } else {
            $modelClass = $modelClassOrInstance;
        }

        if ($modelId === null) {
            throw new \InvalidArgumentException('Model ID must be provided when passing a class name.');
        }

        return DB::table('model_platform')
            ->where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->pluck('platform_id');
    }

    public function addPlatformToModel($model, Platform $platform)
    {
        DB::table('model_platform')->updateOrInsert([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'platform_id' => $platform->id,
        ]);
    }

    public function removePlatformFromModel($model, Platform $platform)
    {
        DB::table('model_platform')->where([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'platform_id' => $platform->id,
        ])->delete();
    }

    public function modelHasPlatform($model, Platform $platform)
    {
        return DB::table('model_platform')->where([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'platform_id' => $platform->id,
        ])->exists();
    }

    public function checkPlatformRelationForModel($modelClass, $modelId, $platformId)
    {
        return DB::table('model_platform')
            ->where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->where('platform_id', $platformId)
            ->exists();
    }
}
