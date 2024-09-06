<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;

class PrepareSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $identifierField;

    protected $identifierValue;

    protected $modelClass;

    protected $eventType;

    protected $platformId;

    public function __construct($identifierField, $identifierValue, $modelClass, $eventType, $platformId)
    {
        $this->identifierField = $identifierField;
        $this->identifierValue = $identifierValue;
        $this->modelClass = $modelClass;
        $this->eventType = $eventType;
        $this->platformId = $platformId;
    }

    public function handle()
    {
        $model = $this->findModel();
        $sourcePlatform = Platform::findOrFail($this->platformId);

        $this->logDebug('Moox Sync: PrepareSyncJob handling', [
            'identifier_field' => $this->identifierField,
            'identifier_value' => $this->identifierValue,
            'model_class' => $this->modelClass,
            'event_type' => $this->eventType,
            'platform_id' => $this->platformId,
        ]);

        $syncData = [
            'event_type' => $this->eventType,
            'model' => $model ? $this->getFullModelData($model) : [$this->identifierField => $this->identifierValue],
            'model_class' => $this->modelClass,
            'platform' => $sourcePlatform->toArray(),
        ];

        $this->logDebug('Moox Sync: Prepared sync data', [
            'sync_data' => $syncData,
        ]);

        $this->invokeWebhooks($syncData);
    }

    protected function findModel()
    {
        return $this->modelClass::where($this->identifierField, $this->identifierValue)->first();
    }

    protected function getFullModelData($model)
    {
        $model->refresh();

        $data = $model->getAttributes();

        if (method_exists($model, 'meta')) {
            $metaData = $model->meta()->pluck('meta_value', 'meta_key')->toArray();
            $data = array_merge($data, $metaData);
        }

        // Special handling for WpUser model
        if ($model instanceof \Moox\Press\Models\WpUser) {
            $data['user_email'] = $model->user_email;
            $data['user_login'] = $model->user_login;
            $data['display_name'] = $model->display_name;
            // Add any other fields that need special handling
        }

        $this->logDebug('Moox Sync: Full model data', [
            'model_class' => get_class($model),
            'data' => $data,
        ]);

        return $data;
    }

    protected function invokeWebhooks(array $data)
    {
        $targetPlatforms = Platform::where('id', '!=', $this->platformId)->get();

        foreach ($targetPlatforms as $targetPlatform) {
            $webhookUrl = 'https://'.$targetPlatform->domain.'/sync-webhook';

            $this->logDebug('Moox Sync: Preparing to invoke webhook', [
                'platform' => $targetPlatform->name,
                'webhook_url' => $webhookUrl,
                'full_data' => $data,
            ]);

            try {
                $response = Http::withToken($targetPlatform->api_token)->post($webhookUrl, $data);

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
}
