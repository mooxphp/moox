<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Tests\Support;

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Heco\FilamentTreeIndex\Contracts\ConfiguresTreeIndex;
use Heco\FilamentTreeIndex\Tests\Models\TreeNode;

final class TestTreeIndexResource implements ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(TreeNode::class);
    }
}
