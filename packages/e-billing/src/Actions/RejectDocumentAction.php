<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class RejectDocumentAction
{
    public function __construct(
        private readonly DocumentApprovalGuard $approvalGuard,
        private readonly RecordApprovalTransitionAction $recordTransition,
    ) {
    }

    public function execute(EbillingDocument $document, string $reason): bool
    {
        $this->approvalGuard->assertCanReject($document);

        $this->recordTransition->executeForAuthenticatedActor(
            document: $document,
            to: DocumentApprovalStatus::Rejected,
            kind: ApprovalTransitionKind::Reject,
            reason: $reason,
        );

        return true;
    }
}
