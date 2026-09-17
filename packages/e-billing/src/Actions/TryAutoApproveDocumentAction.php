<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Approval\AutoApproveEvaluator;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\ApprovalTrigger;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ForwardedSeverityRelease;
use Moox\EBilling\Support\SeverityReleaseSnapshotCollector;

final class TryAutoApproveDocumentAction
{
    public function __construct(
        private readonly AutoApproveEvaluator $evaluator,
        private readonly RecordApprovalTransitionAction $recordTransition,
        private readonly AnnounceDocumentNeedsReviewAction $announceNeedsReview,
        private readonly QueueDocumentDeliveryAction $queueDelivery,
    ) {
    }

    public function execute(EbillingDocument $document): bool
    {
        if (! (bool) config('e-billing.approval.required', true)) {
            return false;
        }

        $result = $this->evaluator->evaluate($document);

        if (! $result->passed()) {
            if ($document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Pending) {
                $this->announceNeedsReview->execute($document);
            }

            return false;
        }

        /** @var list<ForwardedSeverityRelease> $forwardedReleaseReasons */
        $forwardedReleaseReasons = SeverityReleaseSnapshotCollector::collect($document);

        $this->recordTransition->execute(
            document: $document,
            to: DocumentApprovalStatus::Approved,
            kind: ApprovalTransitionKind::Approve,
            trigger: ApprovalTrigger::Auto,
            actorId: RecordApprovalTransitionAction::SYSTEM_ACTOR_ID,
            reason: null,
            forwardedReleaseReasons: $forwardedReleaseReasons,
        );

        $this->queueDelivery->execute($document->fresh() ?? $document);

        return true;
    }
}
