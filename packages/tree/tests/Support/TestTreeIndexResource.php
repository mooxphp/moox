<?php

declare(strict_types=1);

namespace Moox\Tree\Tests\Support;

use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Tree\Tests\Models\TreeNode;

final class TestTreeIndexResource implements ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(TreeNode::class);
    }
}
