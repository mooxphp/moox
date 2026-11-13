<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Moox\EBilling\Actions\RevalidateDocumentAction;
use Moox\EBilling\Actions\ScheduleMasterDataRecheckAction;
use Moox\EBilling\Actions\TryAutoApproveDocumentAction;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Regenerates a document whose master-data recheck is due (ADR 0023), through the revalidate path so review
 * corrections stay. When the recheck no longer applies it ends, and the document gets the approval attempt and
 * reviewer notification it was spared while waiting.
 */
final class RecheckMasterDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /** Seconds to wait when another generation of the document is still running. */
    private const RETRY_WHILE_PIPELINE_RUNS = 60;

    public function __construct(
        public string $ebillingDocumentId,
        public int $attempt,
    ) {
    }

    public function handle(
        ScheduleMasterDataRecheckAction $recheck,
        RevalidateDocumentAction $revalidate,
        TryAutoApproveDocumentAction $tryAutoApprove,
    ): void {
        $document = EbillingDocument::query()->find($this->ebillingDocumentId);

        // Gone, or superseded by another attempt or generation.
        if (! $document instanceof EbillingDocument
            || ! $document->hasPendingMasterDataRecheck()
            || ($document->masterDataRecheck()['attempt'] ?? null) !== $this->attempt) {
            return;
        }

        $refusal = $recheck->stillApplies($document) ? $revalidate->execute($document) : 'no_longer_applies';

        if ($refusal === null) {
            return;
        }

        if ($refusal === 'pipeline_running') {
            $this->release(self::RETRY_WHILE_PIPELINE_RUNS);

            return;
        }

        $recheck->end($document);
        $tryAutoApprove->execute($document->fresh() ?? $document);
    }
}
