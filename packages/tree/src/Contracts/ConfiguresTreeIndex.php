<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Contracts;

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;

interface ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration;
}
