<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\SeverityReleaseSnapshotCollector;

final class ApproveDocumentAction
{
    public function __construct(
        private readonly DocumentApprovalGuard $approvalGuard,
        private readonly RecordApprovalTransitionAction $recordTransition,
    ) {
    }

    public function execute(EbillingDocument $document, ?string $reason = null): bool
    {
        if ($document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Approved) {
            return false;
        }

        $this->approvalGuard->assertCanApprove($document);

        $user = auth()->user();

        if ($user === null) {
            throw new \InvalidArgumentException('An authenticated actor is required to approve a document for dispatch.');
        }

        $this->recordTransition->execute(
            document: $document,
            to: DocumentApprovalStatus::Approved,
            kind: ApprovalTransitionKind::Approve,
            trigger: 'manual',
            actorId: $user->getAuthIdentifier(),
            actorName: $user->name,
            reason: $reason,
            forwardedReleaseReasons: SeverityReleaseSnapshotCollector::collect($document),
        );

        return true;
    }
}
