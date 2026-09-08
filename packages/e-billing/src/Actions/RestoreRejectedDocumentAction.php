<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class RestoreRejectedDocumentAction
{
    public function __construct(
        private readonly DocumentApprovalGuard $approvalGuard,
        private readonly RecordApprovalTransitionAction $recordTransition,
        private readonly AnnounceDocumentNeedsReviewAction $announceNeedsReview,
    ) {
    }

    public function execute(EbillingDocument $document, string $reason): bool
    {
        $this->approvalGuard->assertCanRestore($document);

        $this->recordTransition->executeForAuthenticatedActor(
            document: $document,
            to: DocumentApprovalStatus::Pending,
            kind: ApprovalTransitionKind::Restore,
            reason: $reason,
        );

        $this->announceNeedsReview->execute($document->fresh() ?? $document);

        return true;
    }
}
