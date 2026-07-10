<?php

declare(strict_types=1);

namespace Moox\Connect\Console;

use Illuminate\Console\Command;

final class ConnectQueueListenCommand extends Command
{
    protected $signature = 'connect:queue-listen';

    protected $description = 'Listen to all Connect queues with configured tries and timeout';

    public function handle(): int
    {
        return $this->call('queue:listen', [
            '--queue' => (string) config('connect.queues.worker', 'default,connect-detail'),
            '--tries' => (int) config('connect.queues.worker_tries', 5),
            '--timeout' => (int) config('connect.queues.worker_timeout', 180),
        ]);
    }
}
