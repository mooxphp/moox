<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

enum FailureKind
{
    case Transient;
    case Permanent;
}
