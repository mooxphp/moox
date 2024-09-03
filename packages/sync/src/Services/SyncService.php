<?php

namespace Moox\Sync\Services;

use Moox\Sync\Models\Platform;

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

        if (class_exists($modelClass)) {
            return $this->platformRelationService->modelHasPlatform($model, $targetPlatform);
        }

        return false;
    }
}
