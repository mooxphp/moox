<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum InvoiceOriginRule: string
{
    case CountryDetected = 'country_detected';
    case CountryDetectedDe = 'country_detected_de';
    case NetOnly = 'net_only';
    case DefaultDe = 'default_de';
}
