<?php

declare(strict_types=1);

namespace Moox\Tree\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class NestedSetTreeNode extends Model
{
    use NodeTrait;

    protected $table = 'nested_set_tree_nodes';

    /** @var array<int, string> */
    protected $fillable = [
        'parent_id',
        'label',
        'is_visible',
    ];
}
