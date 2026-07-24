<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Traits\ConfiguresConnectQueue;

final class RunConnectionTreeLevelJob implements ShouldQueue
{
    use ConfiguresConnectQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, array<int>>  $levels
     */
    public function __construct(
        private int $connectionId,
        private array $levels,
        private string $treeRunId,
        private int $levelIndex = 0,
    ) {
        $this->configureConnectQueue('tree_level', connectionId: $this->connectionId);
    }

    public function handle(): void
    {
        $endpointIds = $this->levels[$this->levelIndex] ?? [];
        if ($endpointIds === []) {
            return;
        }

        /** @var array<string, array<int, object>> $jobsByQueue */
        $jobsByQueue = [];

        foreach ($endpointIds as $endpointId) {
            $endpoint = ApiEndpoint::query()->find((int) $endpointId);
            $throwOnFailure = (bool) $endpoint?->option('tree.stop_on_http_error', false);

            $job = $this->levelIndex === 0
                ? new RunEndpointJob((int) $endpointId, $this->treeRunId, $throwOnFailure)
                : new RunDetailForListJob((int) $endpointId, $this->treeRunId, $throwOnFailure);

            $jobsByQueue[$job->queue][] = $job;
        }

        $nextLevelJob = new DispatchConnectionTreeNextLevelJob(
            $this->connectionId,
            $this->levels,
            $this->treeRunId,
            $this->levelIndex + 1,
        );

        $batchIds = [];

        foreach ($jobsByQueue as $queue => $queueJobs) {
            $pending = Bus::batch($queueJobs)
                ->name(sprintf(
                    'connect:tree connection=%d level=%d queue=%s',
                    $this->connectionId,
                    $this->levelIndex,
                    $queue,
                ))
                ->onQueue($queue);

            if (count($jobsByQueue) === 1) {
                $pending->then([$nextLevelJob, '__invoke']);
            }

            $batchIds[] = (string) $pending->dispatch()->id;
        }

        if (count($jobsByQueue) > 1) {
            WaitForConnectionTreeLevelBatchesJob::dispatch(
                $batchIds,
                $this->connectionId,
                $this->levels,
                $this->treeRunId,
                $this->levelIndex + 1,
            );
        }
    }
}
