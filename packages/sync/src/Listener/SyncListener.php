<?php

namespace Moox\Sync\Listener;

use Illuminate\Support\Facades\Event;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;

class SyncListener
{
    protected $currentPlatformId;

    public function __construct()
    {
        $domain = explode('.', request()->getHost())[0];

        $platform = Platform::where('domain', $domain)->first();

        if ($platform) {
            $this->currentPlatformId = $platform->id;
        } else {
            // Handle the case where the platform is not found
            \Log::warning("Platform not found for domain: {$domain}");
            $this->currentPlatformId = null;
        }
    }

    public function registerListeners()
    {
        $syncs = Sync::where('source_platform_id', $this->currentPlatformId)
            ->where('status', true)
            ->get();

        foreach ($syncs as $sync) {
            $this->registerModelListeners($sync);
        }
    }

    protected function registerModelListeners(Sync $sync)
    {
        $modelClass = $sync->source_model;

        Event::listen("eloquent.created: {$modelClass}", function ($model) use ($sync) {
            $this->handleEvent($model, 'created', $sync);
        });

        Event::listen("eloquent.updated: {$modelClass}", function ($model) use ($sync) {
            $this->handleEvent($model, 'updated', $sync);
        });

        Event::listen("eloquent.deleted: {$modelClass}", function ($model) use ($sync) {
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

        $this->invokeWebhook($sync, $syncData);
    }

    protected function invokeWebhook(Sync $sync, array $data)
    {
        $webhookUrl = $sync->targetPlatform->domain.'/sync/webhook';

        \Http::post($webhookUrl, $data);
    }
}
