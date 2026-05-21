<?php

declare(strict_types=1);

namespace Moox\Cache\Enums;

enum CacheTargetStatus: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';
    case Warning = 'warning';
}
