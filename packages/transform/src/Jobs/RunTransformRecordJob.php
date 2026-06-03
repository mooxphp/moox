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

    public int $timeout = 300;

    public function __construct(
        private readonly int $transformRecordId
    ) {}

    public function handle(TransformRunner $runner): void
    {
        $record = TransformRecord::query()->find($this->transformRecordId);
        if (! $record instanceof TransformRecord) {
            return;
        }

        $runner->run($record);
    }
}
