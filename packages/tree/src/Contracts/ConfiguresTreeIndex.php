<?php

declare(strict_types=1);

namespace Moox\Tree\Contracts;

use Moox\Tree\Config\TreeIndexConfiguration;

interface ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration;
}
