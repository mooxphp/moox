<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Moox\Sync\Models\Sync;

class SyncApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $platform;

    public function __construct($platform)
    {
        $this->platform = $platform;
    }

    public function handle()
    {
        // TODO: this might not be the correct URL
        $url = $this->platform->domain.'/api/sync';

        $response = Http::get($url, [
            'platform_id' => $this->platform->id,
        ]);

        if ($response->successful()) {
            $syncConfigs = $response->json();

            foreach ($syncConfigs as $config) {
                Sync::updateOrCreate(
                    ['id' => $config['id']],
                    $config
                );
            }
        } else {
            \Log::error('Failed to fetch sync configurations from the source platform', [
                'platform_id' => $this->platform->id,
                'response' => $response->body(),
            ]);
        }
    }
}
