<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Enums;

enum RelationPresentation: string
{
    case Tab = 'tab';
    case Inline = 'inline';
    case Hidden = 'hidden';
}
