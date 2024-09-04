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

        $otherPlatforms = Platform::where('id', '!=', $this->currentPlatform->id)->get();

        foreach ($otherPlatforms as $targetPlatform) {
            $this->syncPlatform($this->currentPlatform, $targetPlatform);
        }

        $this->logDebug('SyncPlatformJob handle method finished');
    }

    protected function syncPlatform(Platform $sourcePlatform, Platform $targetPlatform)
    {
        try {
            $this->logDebug('Syncing platform', [
                'source' => $sourcePlatform->id,
                'target' => $targetPlatform->id,
            ]);

            $this->sendWebhook($sourcePlatform, $targetPlatform);

        } catch (\Exception $e) {
            $this->logDebug('Error syncing platform', [
                'source' => $sourcePlatform->id,
                'target' => $targetPlatform->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendWebhook(Platform $sourcePlatform, Platform $targetPlatform)
    {
        $webhookUrl = 'https://'.$targetPlatform->domain.'/sync-webhook';

        $data = [
            'event_type' => 'updated',
            'model_class' => Platform::class,
            'model' => $sourcePlatform->toArray(),
            'platform' => $sourcePlatform->toArray(),
        ];

        $response = Http::withToken($targetPlatform->api_token)
            ->post($webhookUrl, $data);

        if ($response->successful()) {
            $this->logDebug('Webhook sent successfully', [
                'source' => $sourcePlatform->name,
                'target' => $targetPlatform->name,
            ]);
        } else {
            $this->logDebug('Webhook failed', [
                'source' => $sourcePlatform->name,
                'target' => $targetPlatform->name,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
