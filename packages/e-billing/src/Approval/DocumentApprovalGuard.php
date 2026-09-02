<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use InvalidArgumentException;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class DocumentApprovalGuard
{
    public function canApprove(EbillingDocument $document): bool
    {
        if (EbillingDocument::hasBlockingMustFieldFindings(
            is_array($document->field_validations) ? $document->field_validations : null,
        )) {
            return false;
        }

        $status = $document->resolveApprovalStatusEnum();

        return $status === DocumentApprovalStatus::Pending;
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
        if (! $this->canApprove($document)) {
            throw new InvalidArgumentException('This document cannot be approved for dispatch.');
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
