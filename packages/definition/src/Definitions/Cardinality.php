<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum Cardinality: string
{
    case Single = 'single';
    case Multiple = 'multiple';
}
