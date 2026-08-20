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
use Moox\MailInbox\Enums\ClaimResult;
use Moox\MailInbox\Exceptions\InvalidSyncCursorException;
use Moox\MailInbox\InboxDriverManager;
use Moox\MailInbox\Models\MailInboxSyncState;
use Moox\MailInbox\Services\MailInboxService;
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
    ) {}

    public function handle(InboxDriverManager $drivers, MailInboxService $inbox): void
    {
        $this->applyMemoryLimit();
        $this->setProgress(0);

        $maxPages = max(1, (int) config('mail-inbox.delta_max_pages_per_poll', 50));
        $maxCursorResetsPerRun = max(0, (int) config('mail-inbox.cursor_reset_max_per_run', 1));
        $cursorResetWarningMinutes = max(1, (int) config('mail-inbox.cursor_reset_warning_minutes', 60));

        $driverName = $drivers->driverNameFor($this->scope);
        $driver = $drivers->mailbox($this->scope);

        $syncState = MailInboxSyncState::query()->firstOrCreate(
            ['scope' => $this->scope],
            [
                'driver' => $driverName,
                'delta_link' => null,
                'last_synced_at' => null,
            ],
        );

        if ($syncState->driver !== null
            && $syncState->driver !== ''
            && $syncState->driver !== $driverName
        ) {
            Log::channel('mail-inbox')->warning('[MailInbox] Sync-state driver mismatch — clearing cursor for fresh sync', [
                'scope' => $this->scope,
                'stored_driver' => $syncState->driver,
                'configured_driver' => $driverName,
            ]);
            $syncState->update([
                'driver' => $driverName,
                'delta_link' => null,
            ]);
        } elseif ($syncState->driver === null || $syncState->driver === '') {
            $syncState->update(['driver' => $driverName]);
        }

        /** @var string|null $cursor null starts a full sync round */
        $cursor = $syncState->delta_link;

        $pagesThisPoll = 0;
        $persistedTotal = 0;
        $skippedKnownTotal = 0;
        $skippedNoAttachmentsTotal = 0;
        $cursorResetsThisRun = 0;

        while (true) {
            try {
                $page = $driver->fetch($cursor);
            } catch (InvalidSyncCursorException $e) {
                if ($cursorResetsThisRun >= $maxCursorResetsPerRun) {
                    Log::channel('mail-inbox')->error('[MailInbox] Sync cursor reset limit reached — aborting fetch', [
                        'scope' => $this->scope,
                        'driver' => $driverName,
                        'cursor_reset_max_per_run' => $maxCursorResetsPerRun,
                        'rejected_host' => $e->rejectedHost,
                        'exception' => $e,
                    ]);

                    throw $e;
                }

                $recentReset = $syncState->cursor_reset_at !== null
                    && $syncState->cursor_reset_at->greaterThan(now()->subMinutes($cursorResetWarningMinutes));

                if ($recentReset) {
                    Log::channel('mail-inbox')->warning('[MailInbox] Repeated sync cursor reset for scope', [
                        'scope' => $this->scope,
                        'driver' => $driverName,
                        'previous_reset_at' => $syncState->cursor_reset_at?->toIso8601String(),
                        'cursor_reset_warning_minutes' => $cursorResetWarningMinutes,
                    ]);
                }

                if ($e->rejectedHost !== null) {
                    Log::channel('mail-inbox')->error('[MailInbox] Sync cursor rejected — unexpected host; clearing token for full resync', [
                        'scope' => $this->scope,
                        'driver' => $driverName,
                        'rejected_host' => $e->rejectedHost,
                        'exception' => $e,
                    ]);
                } else {
                    Log::channel('mail-inbox')->warning('[MailInbox] Sync cursor invalid — clearing token for full resync', [
                        'scope' => $this->scope,
                        'driver' => $driverName,
                        'exception' => $e,
                    ]);
                }

                $cursorResetsThisRun++;
                $syncState->update([
                    'delta_link' => null,
                    'cursor_reset_at' => now(),
                    'catch_up_in_progress' => true,
                ]);
                $cursor = null;

                continue;
            }

            $pagesThisPoll++;

            $result = $inbox->persistMessages($page->messages, $this->scope);
            $persistedTotal += $result->persisted;
            $skippedKnownTotal += $result->skippedKnown;
            $skippedNoAttachmentsTotal += $result->skippedNoAttachments;

            foreach ($page->messages as $dto) {
                try {
                    $claimResult = $driver->claim($dto->externalId);

                    if ($claimResult === ClaimResult::AlreadyHeld) {
                        Log::channel('mail-inbox')->debug('[MailInbox] Message already claimed; skipping claim move', [
                            'external_id' => $dto->externalId,
                            'scope' => $this->scope,
                        ]);

                        continue;
                    }

                    if ($claimResult === ClaimResult::MoveFailed) {
                        Log::channel('mail-inbox')->warning('[MailInbox] claim move failed (best-effort, will retry on next fetch)', [
                            'external_id' => $dto->externalId,
                            'scope' => $this->scope,
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::channel('mail-inbox')->warning('[MailInbox] claim failed (best-effort, will retry on next fetch)', [
                        'external_id' => $dto->externalId,
                        'scope' => $this->scope,
                        'exception_class' => $e::class,
                        'exception_message' => $e->getMessage(),
                    ]);
                }
            }

            $progressCap = max(1, min($pagesThisPoll + 3, $maxPages + 2));
            $this->setProgress((int) min(99, round(($pagesThisPoll / $progressCap) * 100)));

            if ($page->resumeCursor !== null && $page->resumeCursor !== '') {
                $syncState->update([
                    'delta_link' => $page->resumeCursor,
                    'driver' => $driverName,
                    'last_synced_at' => now(),
                    'catch_up_in_progress' => false,
                ]);

                break;
            }

            $next = $page->continuationCursor;
            if ($next === null || $next === '') {
                Log::channel('mail-inbox')->warning('[MailInbox] Message page missing both resumeCursor and continuationCursor', [
                    'scope' => $this->scope,
                ]);

                break;
            }

            if ($pagesThisPoll >= $maxPages) {
                Log::channel('mail-inbox')->warning('[MailInbox] Poll reached delta_max_pages_per_poll; deferring continuation to next poll', [
                    'scope' => $this->scope,
                    'delta_max_pages_per_poll' => $maxPages,
                ]);
                $syncState->update([
                    'delta_link' => $next,
                    'driver' => $driverName,
                    'catch_up_in_progress' => true,
                ]);

                break;
            }

            $cursor = $next;
        }

        Log::channel('mail-inbox')->info('[MailInbox] Sync complete', [
            'scope' => $this->scope,
            'persisted' => $persistedTotal,
            'skipped_known' => $skippedKnownTotal,
            'skipped_no_attachments' => $skippedNoAttachmentsTotal,
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
