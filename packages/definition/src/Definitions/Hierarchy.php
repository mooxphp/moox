<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum Hierarchy: string
{
    case Flat = 'flat';
    case Hierarchical = 'hierarchical';
}
