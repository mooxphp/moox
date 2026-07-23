<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Moox\Connect\Models\ApiEndpoint;

/**
 * Safe tree execution:
 * - Each level is executed as a Batch (all must succeed)
 * - Next level starts only after the Batch finished successfully
 * - On failure, the tree stops (no next job dispatch)
 */
final class RunConnectionTreeLevelJob implements ShouldQueue
{
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
    }

    public function handle(): void
    {
        $endpointIds = $this->levels[$this->levelIndex] ?? [];
        if ($endpointIds === []) {
            return;
        }

        $jobs = [];
        foreach ($endpointIds as $endpointId) {
            $endpoint = ApiEndpoint::query()->find((int) $endpointId);
            $throwOnFailure = (bool) $endpoint?->option('tree.stop_on_http_error', false);

            $jobs[] = $this->levelIndex === 0
                ? new RunEndpointJob((int) $endpointId, $this->treeRunId, $throwOnFailure)
                : new RunDetailForListJob((int) $endpointId, $this->treeRunId, $throwOnFailure);
        }

        Bus::batch($jobs)
            ->name(sprintf('connect:tree connection=%d level=%d', $this->connectionId, $this->levelIndex))
            ->then([
                new DispatchConnectionTreeNextLevelJob(
                    $this->connectionId,
                    $this->levels,
                    $this->treeRunId,
                    $this->levelIndex + 1
                ),
                '__invoke',
            ])
            ->dispatch();
    }
}
