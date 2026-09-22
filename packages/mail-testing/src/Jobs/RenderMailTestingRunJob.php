<?php

declare(strict_types=1);

namespace Moox\MailTesting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Support\MailTestingRunService;

final class RenderMailTestingRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $runId)
    {
        $this->timeout = (int) config('mail-testing.timeout', 0);

        $connection = config('mail-testing.queues.connection');
        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }

        $this->onQueue((string) config('mail-testing.queues.name', 'mail-testing'));
    }

    public function handle(MailTestingRunService $service): void
    {
        $run = MailTestingRun::query()->find($this->runId);

        if (! $run instanceof MailTestingRun) {
            return;
        }

        $service->execute($run);
    }
}
