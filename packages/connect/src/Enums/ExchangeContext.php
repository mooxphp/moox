<?php

declare(strict_types=1);

namespace Moox\Connect\Enums;

enum ExchangeContext: string
{
    case CRON = 'cron';
    case USER = 'user';
    case WEBHOOK = 'webhook';
    case SYSTEM = 'system';
}
