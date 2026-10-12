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
        return $document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Pending
            && ! $this->isPipelineRunning($document);
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
