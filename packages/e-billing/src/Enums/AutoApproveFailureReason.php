<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum AutoApproveFailureReason: string
{
    case GatewayNotValidated = 'gateway_not_validated';
    case HumanReviewRequired = 'human_review_required';
    case MustFieldBlocked = 'must_field_blocked';
    case DuplicateDetected = 'duplicate_detected';
    case AnomalyFlagged = 'anomaly_flagged';
    case AutoApproveDisabled = 'auto_approve_disabled';
    case ApprovalNotPending = 'approval_not_pending';
    case CreditNoteNegativeTotal = 'credit_note_negative_total';

    /**
     * A reviewer changed the document (ADR 0004): a human approves what was re-validated after that.
     */
    case ReviewChanged = 'review_changed';
}
