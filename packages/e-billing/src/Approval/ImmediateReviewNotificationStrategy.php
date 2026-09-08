<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use Moox\EBilling\Contracts\ReviewNotificationStrategyInterface;
use Moox\EBilling\Jobs\NotifyDocumentsNeedReviewJob;
use Moox\EBilling\Models\EbillingDocument;

final class ImmediateReviewNotificationStrategy implements ReviewNotificationStrategyInterface
{
    /**
     * @param  list<string>  $reasons
     */
    public function announce(EbillingDocument $document, array $reasons): void
    {
        NotifyDocumentsNeedReviewJob::dispatch([
            [
                'document_id' => (string) $document->getKey(),
                'reasons' => $reasons,
                'waited_seconds' => 0,
            ],
        ]);
    }
}
