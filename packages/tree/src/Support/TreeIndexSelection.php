<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Builder;

final class TreeIndexSelection
{
    public static function isVisibleInQuery(?int $selectedRecordId, Builder $query): bool
    {
        if ($selectedRecordId === null || $selectedRecordId <= 0) {
            return true;
        }

        return (clone $query)->whereKey($selectedRecordId)->exists();
    }
}
