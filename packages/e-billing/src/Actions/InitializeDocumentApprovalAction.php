<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class InitializeDocumentApprovalAction
{
    public function execute(EbillingDocument $document): bool
    {
        if (! (bool) config('e-billing.approval.required', true)) {
            return false;
        }

        if (! $document->isDeliverable()) {
            return false;
        }

        if ($document->resolveApprovalStatusEnum() !== null) {
            return false;
        }

        $document->approval_status = DocumentApprovalStatus::Pending;
        $document->save();

        return true;
    }
}
