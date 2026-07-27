<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Moox\Connect\Traits\ConfiguresConnectQueue;

final class WaitForConnectionTreeLevelBatchesJob implements ShouldQueue
{
    use ConfiguresConnectQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $backoff = [15, 30, 60, 120];

    /**
     * @param  array<int, string>  $batchIds
     * @param  array<int, array<int>>  $levels
     */
    public function __construct(
        private array $batchIds,
        private int $connectionId,
        private array $levels,
        private string $treeRunId,
        private int $nextLevelIndex,
    ) {
        $this->configureConnectQueue('tree_level', connectionId: $this->connectionId);
    }

    public function handle(): void
    {
        foreach ($this->batchIds as $batchId) {
            $batch = Bus::findBatch($batchId);

            if ($batch === null || ! $batch->finished()) {
                $this->release(15);

                return;
            }

            if ($batch->cancelled() || $batch->failedJobs > 0) {
                return;
            }
        }

        RunConnectionTreeLevelJob::dispatch(
            $this->connectionId,
            $this->levels,
            $this->treeRunId,
            $this->nextLevelIndex,
        );
    }
}
