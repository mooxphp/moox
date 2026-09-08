<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Actions\AnnounceDocumentNeedsReviewAction;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ApprovalEscalation;
use Moox\EBilling\Support\ReviewNotificationCache;
use Moox\EBilling\Support\ReviewNotificationDispatcher;
use Moox\Jobs\Traits\JobProgress;
use Throwable;

/**
 * Finds pending documents past configured escalation thresholds and dispatches
 * one {@see NotifyDocumentsNeedReviewJob} per document for the next unmet level.
 * Does not use {@see \Moox\EBilling\Contracts\ReviewNotificationStrategyInterface}.
 */
final class ScanOverdueApprovalEscalationJob implements ShouldBeUnique, ShouldQueue
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

    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return 'e-billing.scan-overdue-approval-escalation';
    }

    public function handle(AnnounceDocumentNeedsReviewAction $announceNeedsReview): void
    {
        $this->setProgress(0);

        if (! (bool) config('e-billing.approval.required', true)) {
            $this->setProgress(100);

            return;
        }

        $levels = ApprovalEscalation::configuredLevels();

        if ($levels === []) {
            $this->setProgress(100);

            return;
        }

        $now = Carbon::now();
        $dispatched = 0;

        EbillingDocument::query()
            ->where('approval_status', DocumentApprovalStatus::Pending->value)
            ->orderBy('id')
            ->chunkById(100, function (Collection $documents) use ($announceNeedsReview, $levels, $now, &$dispatched): void {
                foreach ($documents as $document) {
                    if ($this->dispatchNextLevel($document, $announceNeedsReview, $levels, $now)) {
                        $dispatched++;
                    }
                }
            });

        Log::info('[EBilling] ScanOverdueApprovalEscalationJob finished', [
            'dispatched' => $dispatched,
        ]);

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::error('[EBilling] ScanOverdueApprovalEscalationJob failed', [
            'exception' => $exception,
        ]);
    }

    /**
     * @param  list<array{key: string, after: int, unit: string}>  $levels
     */
    private function dispatchNextLevel(
        EbillingDocument $document,
        AnnounceDocumentNeedsReviewAction $announceNeedsReview,
        array $levels,
        Carbon $now,
    ): bool {
        $createdAt = $document->created_at;

        if (! $createdAt instanceof Carbon) {
            return false;
        }

        $documentId = (string) $document->getKey();
        $nextLevel = null;

        foreach ($levels as $level) {
            if (! ApprovalEscalation::levelIsMet($level, $createdAt, $now)) {
                continue;
            }

            if (Cache::has(ReviewNotificationCache::escalatedKey($documentId, $level['key']))) {
                continue;
            }

            $nextLevel = $level;
            break;
        }

        if ($nextLevel === null) {
            return false;
        }

        // Lost race on this level: do not claim a higher level in the same pass.
        if (! Cache::add(ReviewNotificationCache::escalatedKey($documentId, $nextLevel['key']), true)) {
            return false;
        }

        ReviewNotificationDispatcher::dispatch([
            [
                'document_id' => $documentId,
                'reasons' => $announceNeedsReview->reasonsFor($document),
                'waited_seconds' => max(0, $createdAt->diffInSeconds($now, false)),
                'escalation_level' => $nextLevel['key'],
            ],
        ]);

        return true;
    }
}
