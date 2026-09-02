<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use Moox\EBilling\Enums\AutoApproveFailureReason;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class AutoApproveEvaluator
{
    public function gatewayValidated(EbillingDocument $document): bool
    {
        return $document->isDeliverable();
    }

    public function allValidationsPassed(EbillingDocument $document): bool
    {
        return ! $document->needsHumanReview();
    }

    public function allMustFieldsPresent(EbillingDocument $document): bool
    {
        return ! EbillingDocument::hasBlockingMustFieldFindings(
            is_array($document->field_validations) ? $document->field_validations : null,
        );
    }

    public function noDuplicate(EbillingDocument $document): bool
    {
        $flags = is_array($document->approval_flags) ? $document->approval_flags : [];

        return ! isset($flags['duplicate']) || ! is_array($flags['duplicate']);
    }

    public function noAnomaly(EbillingDocument $document): bool
    {
        $flags = is_array($document->approval_flags) ? $document->approval_flags : [];
        $anomalies = $flags['anomalies'] ?? null;

        return ! is_array($anomalies) || $anomalies === [];
    }

    public function autoApproveEnabled(): bool
    {
        return (bool) config('e-billing.approval.auto_approve_enabled', true);
    }

    public function approvalIsPending(EbillingDocument $document): bool
    {
        return $document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Pending;
    }

    public function evaluate(EbillingDocument $document): AutoApproveResult
    {
        $failures = [];

        if (! $this->autoApproveEnabled()) {
            $failures[] = AutoApproveFailureReason::AutoApproveDisabled;
        }

        if (! $this->approvalIsPending($document)) {
            $failures[] = AutoApproveFailureReason::ApprovalNotPending;
        }

        if (! $this->gatewayValidated($document)) {
            $failures[] = AutoApproveFailureReason::GatewayNotValidated;
        }

        if (! $this->allValidationsPassed($document)) {
            $failures[] = AutoApproveFailureReason::HumanReviewRequired;
        }

        if (! $this->allMustFieldsPresent($document)) {
            $failures[] = AutoApproveFailureReason::MustFieldBlocked;
        }

        if (! $this->noDuplicate($document)) {
            $failures[] = AutoApproveFailureReason::DuplicateDetected;
        }

        if (! $this->noAnomaly($document)) {
            $failures[] = AutoApproveFailureReason::AnomalyFlagged;
        }

        return new AutoApproveResult($failures);
    }
}
