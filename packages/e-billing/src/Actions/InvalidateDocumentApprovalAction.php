<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class InvalidateDocumentApprovalAction
{
    /**
     * Material document changes void dispatch approval so a prior sign-off cannot
     * authorize data that was re-validated or re-attributed afterwards.
     */
    public function execute(EbillingDocument $document): void
    {
        $status = $document->resolveApprovalStatusEnum();

        if ($status === null || $status === DocumentApprovalStatus::Pending) {
            return;
        }

        $document->approval_status = DocumentApprovalStatus::Pending;
        $document->save();
    }
}
