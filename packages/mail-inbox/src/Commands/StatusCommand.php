<?php

declare(strict_types=1);

namespace Moox\MailInbox\Commands;

use Illuminate\Console\Command;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Services\MailInboxService;

class StatusCommand extends Command
{
    protected $signature = 'mail-inbox:status {--scope=default : Scope to check}';

    protected $description = 'Show current inbox status and message counts';

    public function handle(MailInboxService $service): int
    {
        $scope = (string) $this->option('scope');
        $breakdown = $service->inboxStatusBreakdown($scope);

        $rows = [];
        foreach (InboxMessageProcessingStatus::cases() as $case) {
            $status = $case->value;
            [$messages, $attachments] = $breakdown[$status];
            $rows[] = [$status, (string) $messages, (string) $attachments];
        }

        $this->table(['Status', 'Messages', 'Attachments'], $rows);

        $received = $service->latestReceivedAtForScope($scope);
        $processed = $service->latestProcessedAtForScope($scope);

        $this->newLine();
        $this->info('Latest received: '.($received?->toIso8601String() ?? '—'));
        $this->info('Latest processed: '.($processed?->toIso8601String() ?? '—'));

        return Command::SUCCESS;
    }
}
