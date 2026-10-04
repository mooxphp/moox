<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum OptionsSource: string
{
    case Static = 'static';
    case Enum = 'enum';
    case Definition = 'definition';
    case Provider = 'provider';
}
