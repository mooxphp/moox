<?php

declare(strict_types=1);

namespace Moox\MailInbox\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Services\MailInboxService;
use Throwable;

class ProcessMailCommand extends Command
{
    protected $signature = 'mail-inbox:process {--scope=default : Scope to process} {--message= : Process a specific message by ID} {--retry-failed : Also retry failed messages}';

    protected $description = 'Process inbox messages through the e-billing pipeline';

    public function handle(MailInboxService $service): int
    {
        $scope = (string) $this->option('scope');

        try {
            if ($this->option('message') !== null && $this->option('message') !== '') {
                return $this->handleSingleMessage($service, $scope);
            }

            if ($this->option('retry-failed')) {
                $retried = $service->retryFailedMessages($scope);
                $this->info("Retried {$retried} failed message(s) for scope [{$scope}].");
            } else {
                $processed = $service->processNewMessages($scope);
                $this->info("Processed {$processed} new message(s) in scope [{$scope}].");
            }

            $summary = $service->attachmentTerminalCountsForScope($scope);
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Attachments processed', (string) $summary['processed']],
                    ['Attachments failed', (string) $summary['failed']],
                    ['Attachments skipped', (string) $summary['skipped']],
                ]
            );

            return Command::SUCCESS;
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Process command failed', [
                'exception' => $e,
                'scope' => $scope,
            ]);
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function handleSingleMessage(MailInboxService $service, string $scope): int
    {
        $id = (int) $this->option('message');
        $message = InboxMessage::query()->where('scope', $scope)->find($id);

        if ($message === null) {
            $this->error("No inbox message with id [{$id}] for scope [{$scope}].");

            return Command::FAILURE;
        }

        try {
            $service->enqueueParseJobsForInboxMessage($message);
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Process single message failed', [
                'exception' => $e,
                'inbox_message_id' => $id,
                'scope' => $scope,
            ]);
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $message->refresh();
        $this->info("Processed message [{$id}] — status: {$message->processing_status}");
        $summary = $service->attachmentTerminalCountsForMessage($message);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Attachments processed', (string) $summary['processed']],
                ['Attachments failed', (string) $summary['failed']],
                ['Attachments skipped', (string) $summary['skipped']],
            ]
        );

        return Command::SUCCESS;
    }
}
