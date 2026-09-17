<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ForwardedSeverityRelease;
use Moox\EBilling\Support\SeverityReleaseSnapshotCollector;

final class ApproveDocumentAction
{
    public function __construct(
        private readonly DocumentApprovalGuard $approvalGuard,
        private readonly RecordApprovalTransitionAction $recordTransition,
        private readonly QueueDocumentDeliveryAction $queueDelivery,
    ) {
    }

    public function execute(EbillingDocument $document, ?string $reason = null): bool
    {
        if ($document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Approved) {
            return false;
        }

        $this->approvalGuard->assertCanApprove($document);

        /** @var list<ForwardedSeverityRelease> $forwardedReleaseReasons */
        $forwardedReleaseReasons = SeverityReleaseSnapshotCollector::collect($document);

        $this->recordTransition->executeForAuthenticatedActor(
            document: $document,
            to: DocumentApprovalStatus::Approved,
            kind: ApprovalTransitionKind::Approve,
            reason: $reason,
            forwardedReleaseReasons: $forwardedReleaseReasons,
        );

        $this->queueDelivery->execute($document->fresh() ?? $document);

        return true;
    }
}
