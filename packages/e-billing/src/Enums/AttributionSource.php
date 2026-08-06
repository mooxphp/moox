<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum AttributionSource: string
{
    case Auto = 'auto';
    case Manual = 'manual';
}
