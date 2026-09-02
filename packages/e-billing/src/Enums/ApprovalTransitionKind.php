<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum ApprovalTransitionKind: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Restore = 'restore';

    public function label(): string
    {
        return match ($this) {
            self::Approve => __('e-billing::fields.approval_transition_approve'),
            self::Reject => __('e-billing::fields.approval_transition_reject'),
            self::Restore => __('e-billing::fields.approval_transition_restore'),
        };
    }
}
