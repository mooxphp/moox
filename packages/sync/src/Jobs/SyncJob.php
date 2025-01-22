<?php

namespace Moox\Sync\Jobs;

use Carbon\Month;
use Carbon\WeekDay;
use DateTimeInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;

class SyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use LogLevel;
    use Queueable;
    use SerializesModels;

    public function __construct(protected $modelClass, protected $modelData, protected $eventType, protected Platform $sourcePlatform, protected Platform $targetPlatform, protected bool $shouldDelete) {}

    public function handle(): void
    {
        try {
            if ($this->shouldDelete) {
                $this->deleteModel();
            } elseif ($this->modelClass === Platform::class) {
                // Existing sync logic
                $this->syncPlatform();
            } else {
                $this->syncModel();
            }
        } catch (Exception $exception) {
            Log::error('Moox Sync: Error syncing model', [
                'model_class' => $this->modelClass,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'source_platform' => $this->sourcePlatform->id,
                'target_platform' => $this->targetPlatform->id,
                'should_delete' => $this->shouldDelete,
            ]);
            throw $exception;
        }
    }

    protected function deleteModel()
    {
        $modelId = $this->getModelId();
        $this->logInfo('Moox Sync: Deleting model', [
            'model_class' => $this->modelClass,
            'model_id_field' => $modelId['field'],
            'model_id_value' => $modelId['value'],
        ]);

        DB::table((new $this->modelClass)->getTable())->where($modelId['field'], $modelId['value'])->delete();
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
        $handlerClass = config('sync.sync_bindings.'.$this->modelClass);

        if ($handlerClass && class_exists($handlerClass)) {
            $handler = new $handlerClass($this->modelClass, $this->modelData, $this->eventType);
            $handler->handle();
        } else {
            $this->defaultSync();
        }
    }

    protected function defaultSync()
    {
        $modelId = $this->getModelId();

        if ($this->eventType === 'deleted') {
            DB::table((new $this->modelClass)->getTable())->where($modelId['field'], $modelId['value'])->delete();
        } else {
            $data = $this->formatDatetimeFields($this->modelData);
            DB::table((new $this->modelClass)->getTable())->updateOrInsert(
                [$modelId['field'] => $modelId['value']],
                $data
            );
        }

        $this->logDebug('Model synced successfully', [
            'model_class' => $this->modelClass,
            'model_id_field' => $modelId['field'],
            'model_id_value' => $modelId['value'],
        ]);
    }

    protected function formatDatetimeFields($data)
    {
        foreach (['created_at', 'updated_at'] as $dateField) {
            if (isset($data[$dateField])) {
                $data[$dateField] = $this->formatDatetime($data[$dateField]);
            }
        }

        return $data;
    }

    protected function formatDatetime(DateTimeInterface|WeekDay|Month|string|int|float|null $dateString): string
    {
        return Carbon::parse($dateString)->format('Y-m-d H:i:s');
    }

    protected function getModelId()
    {
        $localIdentifierFields = config('sync.local_identifier_fields', ['ID', 'uuid', 'ulid', 'id']);

        foreach ($localIdentifierFields as $field) {
            if (isset($this->modelData[$field])) {
                return [
                    'field' => $field,
                    'value' => $this->modelData[$field],
                ];
            }
        }

        Log::error('Moox Sync: No suitable ID field found for model', [
            'model_class' => $this->modelClass,
            'model_data' => $this->modelData,
        ]);

        throw new Exception('No valid identifier found for the model');
    }
}
