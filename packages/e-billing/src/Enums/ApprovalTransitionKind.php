<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum ApprovalTransitionKind: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Restore = 'restore';
}
