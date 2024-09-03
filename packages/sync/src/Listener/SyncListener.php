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
    }

    protected function setCurrentPlatform()
    {
        $domain = request()->getHost();

        try {
            $this->currentPlatform = Platform::where('domain', $domain)->first();

            if ($this->currentPlatform) {
                $this->logDebug('Moox Sync: Platform found for domain: '.$domain);
            } else {
                $this->logDebug("Platform not found for domain: {$domain}");
            }
        } catch (QueryException $e) {
            Log::error("Database error occurred while querying for domain: {$domain}. Error: ".$e->getMessage());
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: '.$e->getMessage());
        }
    }

    public function registerListeners()
    {
        if ($this->currentPlatform) {
            $modelsToSync = config('sync.models_with_platform_relations', []);
            foreach ($modelsToSync as $modelClass) {
                $this->registerModelListeners($modelClass);
            }
        }
    }

    protected function registerModelListeners($modelClass)
    {
        Event::listen("eloquent.created: {$modelClass}", function ($model) {
            $this->handleModelEvent($model, 'created');
        });

        Event::listen("eloquent.updated: {$modelClass}", function ($model) {
            $this->handleModelEvent($model, 'updated');
        });

        Event::listen("eloquent.deleted: {$modelClass}", function ($model) {
            $this->handleModelEvent($model, 'deleted');
        });
    }

    protected function handleModelEvent($model, $eventType)
    {
        if (! $this->currentPlatform) {
            return;
        }

        $syncData = [
            'event_type' => $eventType,
            'model' => $model->toArray(),
            'model_class' => get_class($model),
            'platform' => $this->currentPlatform->toArray(),
        ];

        $this->logDebug('Moox Sync: Handling model event', $syncData);

        $this->invokeWebhooks($syncData);
    }

    protected function invokeWebhooks(array $data)
    {
        $targetPlatforms = Platform::where('id', '!=', $this->currentPlatform->id)->get();

        foreach ($targetPlatforms as $targetPlatform) {
            $webhookUrl = 'https://'.$targetPlatform->domain.'/sync-webhook';

            $this->logDebug('Moox Sync: Invoking webhook', ['url' => $webhookUrl, 'data' => $data]);

            try {
                $response = Http::withToken($targetPlatform->api_token)
                    ->post($webhookUrl, $data);

                if ($response->successful()) {
                    $this->logDebug('Moox Sync: Webhook invoked successfully', ['platform' => $targetPlatform->name]);
                } else {
                    $this->logDebug('Moox Sync: Webhook invocation failed', [
                        'platform' => $targetPlatform->name,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                $this->logDebug('Moox Sync: Webhook invocation error', [
                    'platform' => $targetPlatform->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
