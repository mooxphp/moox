<?php

namespace Moox\Training\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasDescendingOrder
{
    protected function getTableQuery(): Builder
    {
        return static::getModel()::query()->latest();
    }
}
