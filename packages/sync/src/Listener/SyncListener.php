<?php

namespace Moox\Sync\Listener;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Jobs\PrepareSyncJob;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;
use Moox\Sync\Services\SyncService;

class SyncListener
{
    use LogLevel;

    protected $currentPlatform;

    protected $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
        $this->setCurrentPlatform();
        $this->logInfo('Moox Sync: SyncListener constructed');
    }

    protected function setCurrentPlatform()
    {
        $domain = request()->getHost();
        $this->logInfo('Moox Sync: Setting current platform for domain', ['domain' => $domain]);

        try {
            $this->currentPlatform = Platform::where('domain', $domain)->first();

            if ($this->currentPlatform) {
                $this->logInfo('Moox Sync: Current platform set', ['platform_id' => $this->currentPlatform->id, 'platform_name' => $this->currentPlatform->name]);
            } else {
                $this->logDebug('Moox Sync: Platform not found for domain', ['domain' => $domain]);
            }
        } catch (QueryException $e) {
            Log::error('Moox Sync: Database error occurred while querying for domain', ['domain' => $domain, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Moox Sync: An unexpected error occurred', ['error' => $e->getMessage()]);
        }
    }

    public function registerListeners()
    {
        $this->logInfo('Moox Sync: Registering listeners');
        if ($this->currentPlatform) {
            $syncsToListen = Sync::where('source_platform_id', $this->currentPlatform->id)->get();
            $this->logInfo('Moox Sync: Syncs to listen', ['count' => $syncsToListen->count(), 'syncs' => $syncsToListen->toArray()]);
            foreach ($syncsToListen as $sync) {
                $this->registerModelListeners($sync->source_model);
            }
            $this->logInfo('Moox Sync: Listeners registered', ['models' => $syncsToListen->pluck('source_model')]);
        } else {
            $this->logDebug('Moox Sync: No listeners registered - current platform not set');
        }
    }

    protected function registerModelListeners($modelClass)
    {
        $this->logInfo('Moox Sync: Registering listeners for model', ['model' => $modelClass]);

        Event::listen("eloquent.created: {$modelClass}", function ($model) use ($modelClass) {
            $localIdentifier = $this->getLocalIdentifier($model);
            $this->logDebug('Moox Sync: Created event triggered', ['model' => $modelClass, 'local_identifier' => $localIdentifier]);
            $this->handleModelEvent($model, 'created');
        });

        Event::listen("eloquent.updated: {$modelClass}", function ($model) use ($modelClass) {
            $localIdentifier = $this->getLocalIdentifier($model);
            $this->logDebug('Moox Sync: Updated event triggered', ['model' => $modelClass, 'local_identifier' => $localIdentifier]);
            $this->handleModelEvent($model, 'updated');
        });

        Event::listen("eloquent.deleted: {$modelClass}", function ($model) use ($modelClass) {
            $localIdentifier = $this->getLocalIdentifier($model);
            $this->logDebug('Moox Sync: Deleted event triggered', ['model' => $modelClass, 'local_identifier' => $localIdentifier]);
            $this->handleModelEvent($model, 'deleted');
        });
    }

    protected function handleModelEvent($model, $eventType)
    {
        if (! $this->currentPlatform) {
            $this->logDebug('Moox Sync: Model event ignored - current platform not set', ['model' => get_class($model), 'event' => $eventType]);

            return;
        }

        $localIdentifier = $this->getLocalIdentifier($model);

        if (! $localIdentifier) {
            $this->logDebug('Moox Sync: Model event ignored - no local identifier found', ['model' => get_class($model), 'event' => $eventType]);

            return;
        }

        $this->logInfo('Dispatching PrepareSyncJob', [
            'model' => get_class($model),
            'local_identifier' => $localIdentifier,
            'event' => $eventType,
            'platform' => $this->currentPlatform->id,
        ]);

        $transformerClass = config('sync.transformer_bindings.'.get_class($model));
        $delay = 0;

        if ($transformerClass && class_exists($transformerClass)) {
            $transformer = new $transformerClass($model);
            $delay = $transformer->getDelay();
        }

        $relevantSyncs = Sync::where('source_platform_id', $this->currentPlatform->id)
            ->where('source_model', get_class($model))
            ->where('status', true)
            ->get()
            ->map(function ($sync) {
                return [
                    'target_platform_id' => $sync->target_platform_id,
                    'target_model' => $sync->target_model,
                ];
            });

        $fileFields = [];
        foreach ($model->getAttributes() as $field => $value) {
            if ($this->isFileField($model, $field)) {
                $fileFields[$field] = [
                    'path' => $value,
                    'size' => @filesize($value),
                    'hash' => @md5_file($value),
                ];
            }
        }

        PrepareSyncJob::dispatch(
            $localIdentifier['field'],
            $localIdentifier['value'],
            get_class($model),
            $eventType,
            $this->currentPlatform->id,
            $relevantSyncs,
            $fileFields
        )->delay(now()->addSeconds($delay));
    }

    protected function getLocalIdentifier($model)
    {
        $localIdentifierFields = config('sync.local_identifier_fields', ['id', 'ID', 'uuid', 'ulid']);

        foreach ($localIdentifierFields as $field) {
            if (isset($model->$field)) {
                return ['field' => $field, 'value' => $model->$field];
            }
        }

        $this->logDebug('No local identifier found for model', ['model' => get_class($model)]);

        return null;
    }

    protected function isFileField($model, $field)
    {
        $fileFieldSearch = config('sync.file_sync_fieldsearch', []);
        foreach ($fileFieldSearch as $search) {
            if (strpos(strtolower($field), strtolower($search)) !== false) {
                return true;
            }
        }

        $resolverClass = config('sync.file_sync_resolver.'.get_class($model));
        if ($resolverClass && class_exists($resolverClass)) {
            $resolver = new $resolverClass($model);

            return $resolver->isFileField($field);
        }

        return false;
    }
}
