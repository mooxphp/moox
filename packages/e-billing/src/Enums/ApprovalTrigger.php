<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum ApprovalTrigger: string
{
    case Manual = 'manual';
    case Auto = 'auto';
}
