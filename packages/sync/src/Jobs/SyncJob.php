<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Core\Traits\LogLevel;
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

    public function handle()
    {
        $this->logDebug('SyncJob handle method entered', [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelData['id'],
            'event_type' => $this->eventType,
            'source_platform' => $this->sourcePlatform->id,
        ]);

        try {
            if ($this->modelClass === Platform::class) {
                $this->syncPlatform();
            } else {
                $this->syncModel();
            }
        } catch (\Exception $e) {
            $this->logDebug('Error syncing model', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelData['id'],
                'error' => $e->getMessage(),
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

    protected function syncModel()
    {
        if (! isset($this->modelData['slug'])) {
            throw new \Exception('Slug field is required for syncing models');
        }

        $model = $this->modelClass::where('slug', $this->modelData['slug'])->first();

        if ($this->eventType === 'deleted') {
            if ($model) {
                $model->delete();
                $this->logDebug('Model deleted successfully', [
                    'model_class' => $this->modelClass,
                    'model_id' => $model->id,
                    'model_slug' => $model->slug,
                ]);
            }
        } else {
            $model = $this->modelClass::updateOrCreate(
                ['slug' => $this->modelData['slug']],
                $this->modelData
            );

            $this->logDebug('Model synced successfully', [
                'model_class' => $this->modelClass,
                'model_id' => $model->id,
                'model_slug' => $model->slug,
            ]);
        }
    }
}
