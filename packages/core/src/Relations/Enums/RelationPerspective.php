<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Enums;

enum RelationPerspective: string
{
    case Owner = 'owner';
    case Related = 'related';
}
