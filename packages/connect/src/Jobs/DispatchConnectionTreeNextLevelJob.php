<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class DispatchConnectionTreeNextLevelJob implements ShouldQueue
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
        private int $nextLevelIndex,
    ) {
    }

    public function __invoke(Batch $batch): void
    {
        RunConnectionTreeLevelJob::dispatch(
            $this->connectionId,
            $this->levels,
            $this->treeRunId,
            $this->nextLevelIndex
        );
    }

    public function handle(): void
    {
        // Kept for normal job dispatching.
        RunConnectionTreeLevelJob::dispatch($this->connectionId, $this->levels, $this->treeRunId, $this->nextLevelIndex);
    }
}
