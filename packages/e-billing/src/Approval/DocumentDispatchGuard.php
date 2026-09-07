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
        if (! $document->isDeliverable()) {
            return false;
        }

        if ($document->needsHumanReview()) {
            return false;
        }

        if (! $this->isApprovalRequired()) {
            return true;
        }

        $status = $document->resolveApprovalStatusEnum();

        if ($status !== DocumentApprovalStatus::Approved) {
            return false;
        }

        return $this->hasApprovalActorAndActedAt($document);
    }

    public function assertDispatchable(EbillingDocument $document): void
    {
        if ($this->isDispatchable($document)) {
            return;
        }

        throw new DocumentNotDispatchableException($this->dispatchBlockReason($document) ?? 'not_dispatchable');
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
