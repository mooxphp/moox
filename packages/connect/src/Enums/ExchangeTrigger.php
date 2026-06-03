<?php

declare(strict_types=1);

namespace Moox\Connect\Enums;

enum ExchangeTrigger: string
{
    case EXPIRED = 'expired';
    case REQUESTED = 'requested';
    case WEBHOOK = 'webhook';
}
