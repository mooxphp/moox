<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ReviewNotificationCache;

final class InvalidateDocumentApprovalAction
{
    /**
     * Material document changes void dispatch approval so a prior sign-off cannot
     * authorize data that was re-validated or re-attributed afterwards.
     */
    public function __construct(
        private readonly AnnounceDocumentNeedsReviewAction $announceNeedsReview,
    ) {
    }

    public function execute(EbillingDocument $document): void
    {
        $status = $document->resolveApprovalStatusEnum();

        if ($status === null || $status === DocumentApprovalStatus::Pending) {
            return;
        }

        ReviewNotificationCache::forgetNotified((string) $document->getKey());

        $document->resetApprovalToPending();
        $document->save();

        $this->announceNeedsReview->execute($document->fresh() ?? $document);
    }
}
