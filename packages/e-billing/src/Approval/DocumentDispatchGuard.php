<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Exceptions\DocumentNotDispatchableException;
use Moox\EBilling\Models\EbillingDocument;

final class DocumentDispatchGuard
{
    public function isApprovalRequired(): bool
    {
        return (bool) config('e-billing.approval.required', true);
    }

    public function isDispatchable(EbillingDocument $document): bool
    {
        return $this->dispatchBlockReason($document) === null;
    }

    public function assertDispatchable(EbillingDocument $document): void
    {
        $reason = $this->dispatchBlockReason($document);

        if ($reason === null) {
            return;
        }

        throw new DocumentNotDispatchableException($reason);
    }

    public function dispatchBlockReason(EbillingDocument $document): ?string
    {
        if (! $document->isDeliverable()) {
            return 'artifact_not_validated';
        }

        if ($document->needsHumanReview()) {
            return 'human_review_required';
        }

        if (! $this->isApprovalRequired()) {
            return null;
        }

        $status = $document->resolveApprovalStatusEnum();

        if ($status === null) {
            return 'approval_not_initialized';
        }

        if ($status === DocumentApprovalStatus::Pending) {
            return 'approval_pending';
        }

        if ($status === DocumentApprovalStatus::Rejected) {
            return 'approval_rejected';
        }

        if ($status !== DocumentApprovalStatus::Approved) {
            return 'approval_pending';
        }

        if (! $this->hasApprovalActorAndActedAt($document)) {
            return 'approval_incomplete';
        }

        return null;
    }

    private function hasApprovalActorAndActedAt(EbillingDocument $document): bool
    {
        $actorId = $document->approval_actor_id;

        if (! is_string($actorId) || trim($actorId) === '') {
            return false;
        }

        return $document->approval_acted_at !== null;
    }
}
