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
    ) {
    }

    public function execute(EbillingDocument $document, string $reason): bool
    {
        $this->approvalGuard->assertCanRestore($document);

        $user = auth()->user();

        if ($user === null) {
            throw new \InvalidArgumentException('An authenticated actor is required to restore a rejected document.');
        }

        $this->recordTransition->execute(
            document: $document,
            to: DocumentApprovalStatus::Pending,
            kind: ApprovalTransitionKind::Restore,
            trigger: 'manual',
            actorId: $user->getAuthIdentifier(),
            actorName: $user->name,
            reason: $reason,
        );

        return true;
    }
}
