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

class SyncPlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    protected $currentPlatform;

    public function __construct()
    {
        $this->currentPlatform = Platform::where('domain', request()->getHost())->first();
    }

    public function handle()
    {
        $this->logDebug('SyncPlatformJob handle method entered');

        if (! $this->currentPlatform) {
            $this->logDebug('Current platform not found. Aborting sync.');

            return;
        }

        $allPlatforms = Platform::all();

        foreach ($allPlatforms as $platform) {
            $this->syncPlatform($platform);
        }

        $this->logDebug('SyncPlatformJob handle method finished');
    }

    protected function syncPlatform(Platform $platform)
    {
        $targetPlatforms = Platform::where('id', '!=', $this->currentPlatform->id)->get();

        foreach ($targetPlatforms as $targetPlatform) {
            try {
                $this->logDebug('Syncing platform', [
                    'source' => $this->currentPlatform->id,
                    'platform' => $platform->id,
                    'target' => $targetPlatform->id,
                ]);

                $this->sendWebhook($platform, $targetPlatform);

            } catch (\Exception $e) {
                $this->logDebug('Error syncing platform', [
                    'source' => $this->currentPlatform->id,
                    'platform' => $platform->id,
                    'target' => $targetPlatform->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function sendWebhook(Platform $platform, Platform $targetPlatform)
    {
        $webhookPath = config('sync.sync_webhook_url', '/sync-webhook');
        $webhookUrl = 'https://'.$targetPlatform->domain.$webhookPath;

        $data = [
            'event_type' => 'updated',
            'model_class' => Platform::class,
            'model' => $platform->toArray(),
            'platform' => $this->currentPlatform->toArray(),
        ];

        $response = Http::withToken($targetPlatform->api_token)
            ->post($webhookUrl, $data);

        if ($response->successful()) {
            $this->logDebug('Webhook sent successfully', [
                'source' => $this->currentPlatform->id,
                'platform' => $platform->id,
                'target' => $targetPlatform->id,
                'webhook_url' => $webhookUrl,
            ]);
        } else {
            $this->logDebug('Webhook failed', [
                'source' => $this->currentPlatform->id,
                'platform' => $platform->id,
                'target' => $targetPlatform->id,
                'webhook_url' => $webhookUrl,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
