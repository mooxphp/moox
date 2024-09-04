<?php

namespace Moox\Sync\Listener;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;
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
        $this->logDebug('Moox Sync: SyncListener constructed');
    }

    protected function setCurrentPlatform()
    {
        $domain = request()->getHost();
        $this->logDebug('Moox Sync: Setting current platform for domain', ['domain' => $domain]);

        try {
            $this->currentPlatform = Platform::where('domain', $domain)->first();

            if ($this->currentPlatform) {
                $this->logDebug('Moox Sync: Current platform set', ['platform' => $this->currentPlatform->id]);
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
        $this->logDebug('Moox Sync: Registering listeners');
        if ($this->currentPlatform) {
            $modelsToSync = config('sync.models_with_platform_relations', []);
            foreach ($modelsToSync as $modelClass) {
                $this->registerModelListeners($modelClass);
            }
            $this->logDebug('Moox Sync: Listeners registered', ['models' => $modelsToSync]);
        } else {
            $this->logDebug('Moox Sync: No listeners registered - current platform not set');
        }
    }

    protected function registerModelListeners($modelClass)
    {
        $this->logDebug('Moox Sync: Registering listeners for model', ['model' => $modelClass]);

        Event::listen("eloquent.created: {$modelClass}", function ($model) use ($modelClass) {
            $this->logDebug('Moox Sync: Created event triggered', ['model' => $modelClass, 'id' => $model->id]);
            $this->handleModelEvent($model, 'created');
        });

        Event::listen("eloquent.updated: {$modelClass}", function ($model) use ($modelClass) {
            $this->logDebug('Moox Sync: Updated event triggered', ['model' => $modelClass, 'id' => $model->id]);
            $this->handleModelEvent($model, 'updated');
        });

        Event::listen("eloquent.deleted: {$modelClass}", function ($model) use ($modelClass) {
            $this->logDebug('Moox Sync: Deleted event triggered', ['model' => $modelClass, 'id' => $model->id]);
            $this->handleModelEvent($model, 'deleted');
        });
    }

    protected function handleModelEvent($model, $eventType)
    {
        if (! $this->currentPlatform) {
            $this->logDebug('Moox Sync: Model event ignored - current platform not set', ['model' => get_class($model), 'id' => $model->id, 'event' => $eventType]);

            return;
        }

        $this->logDebug('Handling model event', [
            'model' => get_class($model),
            'id' => $model->id,
            'event' => $eventType,
            'platform' => $this->currentPlatform->id,
        ]);

        $syncData = [
            'event_type' => $eventType,
            'model' => $model->toArray(),
            'model_class' => get_class($model),
            'platform' => $this->currentPlatform->toArray(),
        ];

        $this->invokeWebhooks($syncData);
    }

    protected function invokeWebhooks(array $data)
    {
        $targetPlatforms = Platform::where('id', '!=', $this->currentPlatform->id)->get();
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
