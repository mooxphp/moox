<?php

declare(strict_types=1);

namespace Moox\MailInbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Exceptions\GraphSyncStateNotFoundException;
use Moox\MailInbox\Models\MailInboxSyncState;
use Moox\MailInbox\Services\GraphMailService;
use Moox\MailInbox\Services\MailInboxService;
use Moox\MailInbox\Support\DeltaMessageInspector;
use Throwable;

class FetchMailsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300];

    public function __construct(
        public string $scope = 'default',
    ) {
    }

    public function handle(GraphMailService $graph, MailInboxService $inbox): void
    {
        $this->applyMemoryLimit();
        $this->setProgress(0);

        $maxPages = max(1, (int) config('mail-inbox.delta_max_pages_per_poll', 50));

        $syncState = MailInboxSyncState::query()->firstOrCreate(
            ['scope' => $this->scope],
            ['delta_link' => null, 'last_synced_at' => null],
        );

        /** @var string|null $continuationUrl null starts a full delta sync round */
        $continuationUrl = $syncState->delta_link;

        $pagesThisPoll = 0;
        $persistedTotal = 0;
        $skippedKnownTotal = 0;
        $skippedNoAttachmentsTotal = 0;
        $removedFilteredTotal = 0;

        while (true) {
            try {
                $page = $graph->fetchInboxMessagesViaDelta($continuationUrl);
            } catch (GraphSyncStateNotFoundException $e) {
                Log::channel('mail-inbox')->warning('[MailInbox] Delta sync state not found — clearing token for full resync', [
                    'scope' => $this->scope,
                    'exception' => $e,
                ]);
                $syncState->update(['delta_link' => null]);
                $continuationUrl = null;

                continue;
            }

            $pagesThisPoll++;

            $result = $inbox->persistDeltaMessages($page->messages, $this->scope);
            $persistedTotal += $result->persisted;
            $skippedKnownTotal += $result->skippedKnown;
            $skippedNoAttachmentsTotal += $result->skippedNoAttachments;
            $removedFilteredTotal += $page->removedFiltered;

            foreach ($page->messages as $graphMessage) {
                if (DeltaMessageInspector::isRemovedPlaceholder($graphMessage)) {
                    continue;
                }

                $messageId = $graphMessage->getId();
                if ($messageId === null || $messageId === '') {
                    continue;
                }

                try {
                    $graph->moveGraphMessageToProcessingFolder($messageId, $this->scope);
                } catch (Throwable $e) {
                    Log::channel('mail-inbox')->warning('[MailInbox] move to Processing folder failed (best-effort, will retry on next delta)', [
                        'messageId' => $messageId,
                        'scope' => $this->scope,
                        'exception_class' => $e::class,
                        'exception_message' => $e->getMessage(),
                    ]);
                }
            }

            $progressCap = max(1, min($pagesThisPoll + 3, $maxPages + 2));
            $this->setProgress((int) min(99, round(($pagesThisPoll / $progressCap) * 100)));

            $deltaUrl = $page->deltaLink;
            if ($deltaUrl !== null && $deltaUrl !== '') {
                $syncState->update([
                    'delta_link' => $deltaUrl,
                    'last_synced_at' => now(),
                ]);

                break;
            }

            $next = $page->nextLink;
            if ($next === null || $next === '') {
                Log::channel('mail-inbox')->warning('[MailInbox] Delta page missing both deltaLink and nextLink', [
                    'scope' => $this->scope,
                ]);

                break;
            }

            if ($pagesThisPoll >= $maxPages) {
                Log::channel('mail-inbox')->warning('[MailInbox] Delta poll reached delta_max_pages_per_poll; deferring continuation to next poll', [
                    'scope' => $this->scope,
                    'delta_max_pages_per_poll' => $maxPages,
                ]);
                $syncState->update(['delta_link' => $next]);

                break;
            }

            $continuationUrl = $next;
        }

        Log::channel('mail-inbox')->info('[MailInbox] Delta sync complete', [
            'scope' => $this->scope,
            'persisted' => $persistedTotal,
            'skipped_known' => $skippedKnownTotal,
            'skipped_no_attachments' => $skippedNoAttachmentsTotal,
            'removed_filtered' => $removedFilteredTotal,
            'total_pages' => $pagesThisPoll,
        ]);

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::channel('mail-inbox')->error('[MailInbox] FetchMailsJob failed', [
            'exception' => $exception,
            'scope' => $this->scope,
        ]);
    }

    private function applyMemoryLimit(): void
    {
        ini_set('memory_limit', (string) config('mail-inbox.memory_limit', '512M'));
    }
}
