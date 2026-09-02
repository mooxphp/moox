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
}
