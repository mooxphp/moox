<?php

declare(strict_types=1);

namespace Moox\Connect\Enums;

enum ExchangeDirection: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';
}
