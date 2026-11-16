<?php

declare(strict_types=1);

namespace Moox\Product\Enums;

enum ProductType: string
{
    case Simple = 'simple';
    case Grouped = 'grouped';
    case Bundle = 'bundle';
    case Configurable = 'configurable';
    case Custom = 'custom';
}
