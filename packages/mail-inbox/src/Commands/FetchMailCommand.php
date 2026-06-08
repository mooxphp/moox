<?php

declare(strict_types=1);

namespace Moox\MailInbox\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Moox\MailInbox\Jobs\FetchMailsJob;
use Throwable;

class FetchMailCommand extends Command
{
    protected $signature = 'mail-inbox:fetch {--scope=default : Scope to fetch for} {--limit= : Override fetch limit}';

    protected $description = 'Run inbox fetch job synchronously (stores rows and queues attachment/PDF jobs)';

    public function handle(): int
    {
        $scope = (string) $this->option('scope');
        $limitOption = $this->option('limit');
        $hadLimitOverride = $limitOption !== null && $limitOption !== '';
        $previousLimit = $hadLimitOverride ? config('mail-inbox.fetch_limit') : null;

        if ($hadLimitOverride) {
            Config::set('mail-inbox.fetch_limit', (int) $limitOption);
        }

        try {
            FetchMailsJob::dispatchSync($scope);
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Fetch command failed', [
                'exception' => $e,
                'scope' => $scope,
            ]);
            $this->error($e->getMessage());

            return Command::FAILURE;
        } finally {
            if ($hadLimitOverride) {
                Config::set('mail-inbox.fetch_limit', $previousLimit);
            }
        }

        $this->info("FetchMailsJob completed for scope [{$scope}] (attachment and PDF jobs may still be on the queue).");

        return Command::SUCCESS;
    }
}
