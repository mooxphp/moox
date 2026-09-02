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

        $user = auth()->user();

        if ($user === null) {
            throw new \InvalidArgumentException('An authenticated actor is required to reject a document.');
        }

        $this->recordTransition->execute(
            document: $document,
            to: DocumentApprovalStatus::Rejected,
            kind: ApprovalTransitionKind::Reject,
            trigger: 'manual',
            actorId: $user->getAuthIdentifier(),
            actorName: $user->name,
            reason: $reason,
        );

        return true;
    }
}
