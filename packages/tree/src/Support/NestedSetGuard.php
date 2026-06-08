<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kalnoy\Nestedset\NodeTrait;

final class NestedSetGuard
{
    /**
     * @param  class-string<Model>|Model  $model
     */
    public static function assertCapable(string|Model $model): void
    {
        $class = is_string($model) ? $model : $model::class;

        if (! in_array(NodeTrait::class, class_uses_recursive($class), true)) {
            throw new InvalidArgumentException(
                'Nested set tree index requires Kalnoy\Nestedset\NodeTrait on the model.',
            );
        }
    }
}
