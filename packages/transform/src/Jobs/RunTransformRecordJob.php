<?php

declare(strict_types=1);

namespace Moox\Transform\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\TransformRunner;

class RunTransformRecordJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 0;

    public function __construct(
        private readonly int $transformRecordId
    ) {
        $timeout = config('transform.job_timeout', 0);
        $this->timeout = is_int($timeout) ? $timeout : 0;
        $this->onQueue((string) config('transform.job_queue', 'transform'));
    }

    public function handle(TransformRunner $runner): void
    {
        $record = TransformRecord::query()->find($this->transformRecordId);
        if (! $record instanceof TransformRecord) {
            return;
        }

        $runner->run($record);
    }
}
