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

class PrepareSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $modelId;

    protected $modelClass;

    protected $eventType;

    protected $platformId;

    public function __construct($modelId, $modelClass, $eventType, $platformId)
    {
        $this->modelId = $modelId;
        $this->modelClass = $modelClass;
        $this->eventType = $eventType;
        $this->platformId = $platformId;
    }

    public function handle()
    {
        $this->logDebug('DeferredSyncJob started', [
            'model_id' => $this->modelId,
            'model_class' => $this->modelClass,
            'event_type' => $this->eventType,
            'platform_id' => $this->platformId,
        ]);

        $model = $this->modelClass::findOrFail($this->modelId);
        $platform = Platform::findOrFail($this->platformId);

        $modelData = $model->toArray();

        if ($model instanceof \Moox\Press\Models\WpUser) {
            $userMeta = $model->getAllMetaAttributes();
            $this->logDebug('User meta data retrieved in deferred job', ['user_meta' => $userMeta]);
            $modelData = array_merge($modelData, $userMeta);
        }

        $syncData = [
            'event_type' => $this->eventType,
            'model' => $modelData,
            'model_class' => $this->modelClass,
            'platform' => $platform->toArray(),
        ];

        $this->logDebug('Sync data prepared in deferred job', ['sync_data' => $syncData]);

        $this->invokeWebhooks($syncData);
    }

    protected function invokeWebhooks(array $data)
    {
        $targetPlatforms = Platform::where('id', '!=', $this->platformId)->get();
        $this->logDebug('Moox Sync: Invoking webhooks', ['target_platforms' => $targetPlatforms->pluck('id')]);

        foreach ($targetPlatforms as $targetPlatform) {
            $webhookUrl = 'https://'.$targetPlatform->domain.'/sync-webhook';

            $this->logDebug('Moox Sync: Sending webhook', ['url' => $webhookUrl, 'target_platform' => $targetPlatform->id]);

            try {
                $response = Http::withToken($targetPlatform->api_token)
                    ->post($webhookUrl, $data);

                if ($response->successful()) {
                    $this->logDebug('Moox Sync: Webhook sent successfully', ['target_platform' => $targetPlatform->id]);
                } else {
                    Log::error('Moox Sync: Webhook failed', [
                        'target_platform' => $targetPlatform->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Moox Sync: Webhook error', [
                    'target_platform' => $targetPlatform->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
