<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use InvalidArgumentException;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\CreditNoteSign;

final class DocumentApprovalGuard
{
    public const BLOCK_NOT_PENDING = 'not_pending';

    public const BLOCK_PIPELINE_RUNNING = 'pipeline_running';

    public const BLOCK_ARTIFACT_FAILED = 'artifact_failed';

    public const BLOCK_ARTIFACT_NOT_VALIDATED = 'artifact_not_validated';

    public const BLOCK_HUMAN_REVIEW_REQUIRED = 'human_review_required';

    public const BLOCK_CREDIT_NOTE_NEGATIVE_TOTAL = 'credit_note_negative_total';

    public const BLOCK_MUST_FIELD_MISSING = 'must_field_missing';

    public function __construct(
        private readonly DocumentEditGuard $editGuard,
    ) {
    }

    public function canApprove(EbillingDocument $document): bool
    {
        return $this->blockReason($document) === null;
    }

    /**
     * Why the document cannot be approved, as one of the `BLOCK_*` codes, or null when it can. The view
     * explains a pending document's reason (`e-billing::fields.approval_blocked.*`); `not_pending` is never shown.
     */
    public function blockReason(EbillingDocument $document): ?string
    {
        if ($document->resolveApprovalStatusEnum() !== DocumentApprovalStatus::Pending) {
            return self::BLOCK_NOT_PENDING;
        }

        if ($this->editGuard->isPipelineRunning($document)) {
            return self::BLOCK_PIPELINE_RUNNING;
        }

        if (! $document->isDeliverable()) {
            return $document->gateway_status?->isFailure() === true
                ? self::BLOCK_ARTIFACT_FAILED
                : self::BLOCK_ARTIFACT_NOT_VALIDATED;
        }

        if (EbillingDocument::missingMustFields(
            is_array($document->field_validations) ? $document->field_validations : null,
            $document->profileDocumentType(),
        ) !== []) {
            return self::BLOCK_MUST_FIELD_MISSING;
        }

        // Confirming accepts open needs_review findings (ADR 0005 amendment).
        if ($document->hasUnacceptedReviewFindings()) {
            return self::BLOCK_HUMAN_REVIEW_REQUIRED;
        }

        if (CreditNoteSign::hasNegativeTotal($document->invoice)) {
            return self::BLOCK_CREDIT_NOTE_NEGATIVE_TOTAL;
        }

        return null;
    }

    public function canReject(EbillingDocument $document): bool
    {
        return $document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Pending;
    }

    public function canRestore(EbillingDocument $document): bool
    {
        return $document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Rejected;
    }

    public function assertCanApprove(EbillingDocument $document): void
    {
        $reason = $this->blockReason($document);

        if ($reason !== null) {
            throw new InvalidArgumentException("This document cannot be approved for dispatch ({$reason}).");
        }
    }

    public function assertCanReject(EbillingDocument $document): void
    {
        if (! $this->canReject($document)) {
            throw new InvalidArgumentException('This document cannot be rejected.');
        }
    }

    public function assertCanRestore(EbillingDocument $document): void
    {
        if (! $this->canRestore($document)) {
            throw new InvalidArgumentException('This document cannot be restored to pending approval.');
        }
    }
}
