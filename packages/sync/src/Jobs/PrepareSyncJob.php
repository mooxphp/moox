<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;
use Moox\Sync\Services\PlatformRelationService;

class PrepareSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $identifierField;

    protected $identifierValue;

    protected $modelClass;

    protected $eventType;

    protected $platformId;

    protected $syncConfigurations;

    protected $sourcePlatform;

    protected $modelData;

    public function __construct($identifierField, $identifierValue, $modelClass, $eventType, $platformId, $syncConfigurations)
    {
        $this->identifierField = $identifierField;
        $this->identifierValue = $identifierValue;
        $this->modelClass = $modelClass;
        $this->eventType = $eventType;
        $this->platformId = $platformId;
        $this->syncConfigurations = $syncConfigurations;
        $this->sourcePlatform = Platform::findOrFail($platformId);
        $this->modelData = $this->findModel()->toArray();
    }

    public function handle(PlatformRelationService $platformRelationService)
    {
        $sync = Sync::where('source_model', $this->modelClass)
            ->where('source_platform_id', $this->sourcePlatform->id)
            ->first();

        if (! $sync || ! $sync->use_platform_relations) {
            // Existing logic for syncing to all platforms
            return;
        }

        $modelId = $this->getModelId($this->modelData);
        $relatedPlatforms = $platformRelationService->getPlatformsForModel($this->modelClass, $modelId);

        $allPlatforms = Platform::where('id', '!=', $this->sourcePlatform->id)->get();

        foreach ($allPlatforms as $platform) {
            if ($relatedPlatforms->contains($platform->id)) {
                $this->syncToPlatform($platform, false);
            } else {
                $this->syncToPlatform($platform, true);
            }
        }
    }

    protected function syncToPlatform(Platform $platform, bool $shouldDelete)
    {
        dispatch(new SyncJob(
            $this->modelClass,
            $this->modelData,
            $this->eventType,
            $this->sourcePlatform,
            $platform,
            $shouldDelete
        ));
    }

    protected function findModel()
    {
        $this->logDebug('Moox Sync: Finding model', [
            'model_class' => $this->modelClass,
            'identifier_field' => $this->identifierField,
            'identifier_value' => $this->identifierValue,
        ]);

        $model = $this->modelClass::where($this->identifierField, $this->identifierValue)->first();

        if (! $model) {
            Log::warning('Moox Sync: Model not found', [
                'model_class' => $this->modelClass,
                'identifier_field' => $this->identifierField,
                'identifier_value' => $this->identifierValue,
            ]);
        } else {
            $this->logDebug('Moox Sync: Model found', [
                'model_class' => $this->modelClass,
                'model_data' => $model->toArray(),
            ]);
        }

        return $model;
    }

    protected function getFullModelData($model)
    {
        $this->logDebug('Moox Sync: Getting full model data', [
            'model_class' => $this->modelClass,
            'identifier_field' => $this->identifierField,
            'identifier_value' => $this->identifierValue,
        ]);

        $transformerClass = config("sync.transformer_bindings.{$this->modelClass}");

        if ($transformerClass && class_exists($transformerClass)) {
            $transformer = new $transformerClass($model);
            $transformedData = $transformer->transform();

            $this->logDebug('Moox Sync: Transformed data', [
                'transformed_data' => $transformedData,
            ]);

            return $transformedData;
        }

        return $model->toArray();
    }

    protected function invokeWebhooks(array $data)
    {
        $webhookPath = config('sync.sync_webhook_url', '/sync-webhook');
        $syncToken = config('sync.sync_token');

        foreach ($this->syncConfigurations as $syncConfig) {
            $targetPlatform = Platform::findOrFail($syncConfig['target_platform_id']);
            $webhookUrl = 'https://'.$targetPlatform->domain.$webhookPath;

            $payload = json_encode($data);
            $signature = hash_hmac('sha256', $payload, $targetPlatform->api_token.$syncToken);

            $this->logDebug('Moox Sync: Preparing to invoke webhook', [
                'platform' => $targetPlatform->name,
                'webhook_url' => $webhookUrl,
                'full_data' => $data,
            ]);

            try {
                $response = Http::withHeaders([
                    'X-Platform-Token' => $targetPlatform->api_token ?? $syncToken,
                    'X-Webhook-Signature' => $signature,
                ])->post($webhookUrl, $data);

                $this->logDebug('Moox Sync: Webhook invoked', [
                    'platform' => $targetPlatform->name,
                    'webhook_url' => $webhookUrl,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            } catch (\Exception $e) {
                $this->logDebug('Moox Sync: Webhook invocation error', [
                    'platform' => $targetPlatform->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    protected function getModelId($modelData)
    {
        $identifierFields = config('sync.local_identifier_fields', ['ID', 'uuid', 'ulid', 'id']);

        foreach ($identifierFields as $field) {
            if (isset($modelData[$field])) {
                return $modelData[$field];
            }
        }

        return $modelData[$this->identifierField] ?? null;
    }
}
