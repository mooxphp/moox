<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface PreparesFeedItems
{
    /**
     * @param  Collection<int, Model>  $models
     */
    public function prepare(Collection $models, string $locale): void;
}
