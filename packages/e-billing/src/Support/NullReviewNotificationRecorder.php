<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\ReviewNotificationRecorderInterface;

/**
 * Default recorder — e-billing never requires moox/audit.
 */
final class NullReviewNotificationRecorder implements ReviewNotificationRecorderInterface
{
    /**
     * @param  list<array{document_id: string, reasons: list<string>, waited_seconds: int, escalation_level?: string}>  $documents
     */
    public function record(array $documents): void
    {
    }
}
