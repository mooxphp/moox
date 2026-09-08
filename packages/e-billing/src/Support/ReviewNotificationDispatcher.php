<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\ReviewNotificationRecorderInterface;
use Moox\EBilling\Jobs\NotifyDocumentsNeedReviewJob;

/**
 * Single path for dispatching review-notification jobs. Optional recording
 * (e.g. moox/audit Activity) is bound via {@see ReviewNotificationRecorderInterface}.
 */
final class ReviewNotificationDispatcher
{
    /**
     * @param  list<array{document_id: string, reasons: list<string>, waited_seconds: int, escalation_level?: string}>  $documents
     */
    public static function dispatch(array $documents): void
    {
        NotifyDocumentsNeedReviewJob::dispatch($documents);

        app(ReviewNotificationRecorderInterface::class)->record($documents);
    }
}
