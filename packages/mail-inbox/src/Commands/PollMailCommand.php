<?php

declare(strict_types=1);

namespace Moox\MailInbox\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Moox\MailInbox\Jobs\FetchMailsJob;
use Throwable;

class PollMailCommand extends Command
{
    protected $signature = 'mail-inbox:poll {--scope=default : Scope to poll for}';

    protected $description = 'Dispatch inbox fetch job (pipeline continues via queue)';

    public function handle(): int
    {
        $scope = (string) $this->option('scope');

        try {
            FetchMailsJob::dispatch($scope);
            Log::channel('mail-inbox')->debug('[MailInbox] Poll dispatched FetchMailsJob', ['scope' => $scope]);
            $this->line("[{$scope}] FetchMailsJob dispatched to queue.");
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Poll command failed', [
                'exception' => $e,
                'scope' => $scope,
            ]);
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
