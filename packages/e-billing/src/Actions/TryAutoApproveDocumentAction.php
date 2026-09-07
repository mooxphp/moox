<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Approval\AutoApproveEvaluator;
use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class TryAutoApproveDocumentAction
{
    public function __construct(
        private readonly AutoApproveEvaluator $evaluator,
        private readonly DocumentApprovalGuard $approvalGuard,
        private readonly RecordApprovalTransitionAction $recordTransition,
    ) {
    }

    public function execute(EbillingDocument $document): bool
    {
        if (! (bool) config('e-billing.approval.required', true)) {
            return false;
        }

        if (! $this->approvalGuard->canApprove($document)) {
            return false;
        }

        $result = $this->evaluator->evaluate($document);

        if (! $result->passed()) {
            return false;
        }

        $this->recordTransition->execute(
            document: $document,
            to: DocumentApprovalStatus::Approved,
            kind: ApprovalTransitionKind::Approve,
            trigger: 'auto',
            actorId: RecordApprovalTransitionAction::SYSTEM_ACTOR_ID,
            reason: null,
        );

        return true;
    }
}
