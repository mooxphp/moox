<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

/**
 * What a reviewer did to a document (mooxphp/e-billing#40). Only a value correction means
 * "the parser read this wrong"; approvals, rejections and severity releases never do.
 */
enum ReviewerActionType: string
{
    case ValueCorrection = 'value_correction';
    case Approval = 'approval';
    case Rejection = 'rejection';
    case SeverityRelease = 'severity_release';
}
