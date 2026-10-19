<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use InvalidArgumentException;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Who may change a document in the review workspace (ADR 0004). Nothing changes while generation or
 * validation runs: the artifact being produced would silently miss the change.
 */
final class DocumentEditGuard
{
    public function canEdit(EbillingDocument $document): bool
    {
        return $this->awaitsApprovalDecision($document)
            && ! $this->isPipelineRunning($document);
    }

    /**
     * Pending, or no approval decision on an artifact that is not deliverable: an artifact that failed validation
     * never enters approval, and that is exactly when a correction is needed (mooxphp/e-billing#109). A deliverable
     * document without approval (approval not required) may be dispatched any moment, and a delivered one stays locked.
     */
    public function awaitsApprovalDecision(EbillingDocument $document): bool
    {
        return match ($document->resolveApprovalStatusEnum()) {
            DocumentApprovalStatus::Pending => true,
            null => ! $document->isDeliverable() && ! $document->deliveryAttempts()->exists(),
            default => false,
        };
    }

    public function isPipelineRunning(EbillingDocument $document): bool
    {
        return in_array($document->gateway_status, [
            EBillingAttachmentProcessingStatus::Generating,
            EBillingAttachmentProcessingStatus::Validating,
        ], true);
    }

    public function assertPipelineIdle(EbillingDocument $document): void
    {
        if ($this->isPipelineRunning($document)) {
            throw new InvalidArgumentException('The document cannot be changed while its artifact is generated and validated.');
        }
    }
}
