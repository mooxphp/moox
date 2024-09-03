<?php

namespace Moox\Sync\Listener;

use Illuminate\Database\QueryException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;
use Moox\Sync\Services\SyncService;

class SyncListener
{
    use LogLevel;

    protected $currentPlatformId;

    protected $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
        $domain = explode('.', request()->getHost());

        if (is_array($domain)) {
            $domain = implode('.', $domain);
        }

        try {
            $sync = Sync::first();

            if ($sync) {
                $platform = Platform::where('domain', $domain)->first();

                if ($platform) {
                    $this->currentPlatformId = $platform->id;
                    $this->logDebug('Moox Sync: Platform found for domain: '.$domain);
                } else {
                    $this->logDebug("Platform not found for domain: {$domain}");
                    $this->currentPlatformId = null;
                }
            }
        } catch (QueryException $e) {
            Log::error("Database error occurred while querying for domain: {$domain}. Error: ".$e->getMessage());
            $this->currentPlatformId = null;
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred: '.$e->getMessage());
            $this->currentPlatformId = null;
        }
    }

    public function registerListeners()
    {
        if ($this->currentPlatformId) {
            $syncs = Sync::where('source_platform_id', $this->currentPlatformId)
                ->where('status', true)
                ->get();
            foreach ($syncs as $sync) {
                $this->registerModelListeners($sync);
            }
        }
        $this->logDebug('SyncListener registered', ['platform_id' => $this->currentPlatformId]);
    }

    protected function registerModelListeners(Sync $sync)
    {
        $modelClass = $sync->source_model;

        $this->logDebug('Moox Sync: Listen to Events for '.$modelClass);

        Event::listen("eloquent.created: {$modelClass}", function ($model) use ($sync) {
            $this->logDebug('Moox Sync: Event created for '.$model->id);
            $this->handleEvent($model, 'created', $sync);
        });

        Event::listen("eloquent.updated: {$modelClass}", function ($model) use ($sync) {
            $this->logDebug('Moox Sync: Event updated for '.$model->title);
            $this->handleEvent($model, 'updated', $sync);
        });

        Event::listen("eloquent.deleted: {$modelClass}", function ($model) use ($sync) {
            $this->logDebug('Moox Sync: Event deleted for '.$model->id);
            $this->handleEvent($model, 'deleted', $sync);
        });
    }

    protected function handleEvent($model, $eventType, Sync $sync)
    {
        $data = $model->toArray();
        $syncData = [
            'event_type' => $eventType,
            'model' => $data,
            'sync' => $sync->toArray(),
        ];

        $this->logDebug('Moox Sync: Invoke Webhook for '.$this->currentPlatformId);

        $this->invokeWebhook($sync, $syncData);
    }

    protected function invokeWebhook(Sync $sync, array $data)
    {
        $webhookUrl = 'https://'.$sync->targetPlatform->domain.'/sync-webhook';

        $this->logDebug('Moox Sync: Push to Webhook:', ['url' => $webhookUrl, 'data' => $data]);

        try {
            $response = Http::asJson()->post($webhookUrl, $data);

            if ($response->successful()) {
                $this->logDebug('Moox Sync: Webhook invoked successfully.', ['url' => $webhookUrl]);
            } elseif ($response->clientError()) {
                Log::warning('Client error occurred when invoking webhook.', [
                    'url' => $webhookUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            } elseif ($response->serverError()) {
                Log::error('Server error occurred when invoking webhook.', [
                    'url' => $webhookUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            } else {
                Log::warning('Unexpected status code returned when invoking webhook.', [
                    'url' => $webhookUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (RequestException $e) {
            Log::error('Error occurred during HTTP request to webhook.', [
                'url' => $webhookUrl,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Unexpected error occurred.', [
                'url' => $webhookUrl,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
