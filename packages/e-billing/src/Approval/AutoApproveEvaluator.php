<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use Moox\EBilling\Enums\AutoApproveFailureReason;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class AutoApproveEvaluator
{
    public function evaluate(EbillingDocument $document): AutoApproveResult
    {
        $failures = [];

        if (! (bool) config('e-billing.approval.auto_approve_enabled', true)) {
            $failures[] = AutoApproveFailureReason::AutoApproveDisabled;
        }

        if ($document->resolveApprovalStatusEnum() !== DocumentApprovalStatus::Pending) {
            $failures[] = AutoApproveFailureReason::ApprovalNotPending;
        }

        if (! $document->isDeliverable()) {
            $failures[] = AutoApproveFailureReason::GatewayNotValidated;
        }

        if ($document->needsHumanReview()) {
            $failures[] = AutoApproveFailureReason::HumanReviewRequired;
        }

        if (EbillingDocument::hasBlockingMustFieldFindings(
            is_array($document->field_validations) ? $document->field_validations : null,
        )) {
            $failures[] = AutoApproveFailureReason::MustFieldBlocked;
        }

        if ($document->hasDuplicateApprovalFlag()) {
            $failures[] = AutoApproveFailureReason::DuplicateDetected;
        }

        if ($document->hasAnomalyApprovalFlags()) {
            $failures[] = AutoApproveFailureReason::AnomalyFlagged;
        }

        return new AutoApproveResult($failures);
    }
}
