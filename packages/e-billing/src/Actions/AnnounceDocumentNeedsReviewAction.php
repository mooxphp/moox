<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\Cache;
use Moox\EBilling\Approval\AutoApproveEvaluator;
use Moox\EBilling\Contracts\ReviewNotificationStrategyInterface;
use Moox\EBilling\Enums\AutoApproveFailureReason;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Events\DocumentEnteredReview;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ReviewNotificationCache;

final class AnnounceDocumentNeedsReviewAction
{
    public const AWAITING_APPROVAL_REASON = 'awaiting_approval';

    public function __construct(
        private readonly AutoApproveEvaluator $evaluator,
        private readonly ReviewNotificationStrategyInterface $strategy,
    ) {
    }

    public function execute(EbillingDocument $document): void
    {
        if (! (bool) config('e-billing.approval.required', true)) {
            return;
        }

        if ($document->resolveApprovalStatusEnum() !== DocumentApprovalStatus::Pending) {
            return;
        }

        $documentId = (string) $document->getKey();

        if (! Cache::add(ReviewNotificationCache::notifiedKey($documentId), true)) {
            return;
        }

        $reasons = array_map(
            static fn (AutoApproveFailureReason $reason): string => $reason->value,
            $this->evaluator->evaluate($document)->failures(),
        );

        if ($reasons === []) {
            $reasons = [self::AWAITING_APPROVAL_REASON];
        }

        event(new DocumentEnteredReview(
            document: $document,
            reasons: $reasons,
        ));

        $this->strategy->announce($document, $reasons);
    }
}
