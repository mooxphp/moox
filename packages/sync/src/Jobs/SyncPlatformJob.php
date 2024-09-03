<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Core\Traits\LogLevel;
use Moox\Sync\Models\Platform;
use Moox\Sync\Services\SyncService;

class SyncPlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LogLevel, Queueable, SerializesModels;

    public function handle(SyncService $syncService)
    {
        $this->logDebug('SyncPlatformJob handle method entered');

        $platforms = Platform::all();

        foreach ($platforms as $sourcePlatform) {
            $this->syncPlatform($syncService, $sourcePlatform);
        }

        $this->logDebug('SyncPlatformJob handle method finished');
    }

    protected function syncPlatform(SyncService $syncService, Platform $sourcePlatform)
    {
        $targetPlatforms = Platform::where('id', '!=', $sourcePlatform->id)->get();

        foreach ($targetPlatforms as $targetPlatform) {
            try {
                $this->logDebug('Syncing platform', [
                    'source' => $sourcePlatform->id,
                    'target' => $targetPlatform->id,
                ]);

                $syncService->performSync(
                    Platform::class,
                    $sourcePlatform->toArray(),
                    'updated',
                    $sourcePlatform,
                    $targetPlatform
                );

            } catch (\Exception $e) {
                $this->logDebug('Error syncing platform', [
                    'source' => $sourcePlatform->id,
                    'target' => $targetPlatform->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
