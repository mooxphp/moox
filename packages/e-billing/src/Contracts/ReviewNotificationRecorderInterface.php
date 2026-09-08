<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

/**
 * Optional side-effect when review-notification jobs are dispatched.
 * Default binding is a no-op; hosts opt in via `e-billing.notification.recorder`.
 */
interface ReviewNotificationRecorderInterface
{
    public const EVENT_REVIEW_NOTIFICATION = 'review_notification_dispatched';

    public const EVENT_APPROVAL_ESCALATION = 'approval_escalation_notified';

    /**
     * @param  list<array{document_id: string, reasons: list<string>, waited_seconds: int, escalation_level?: string}>  $documents
     */
    public function record(array $documents): void;
}
