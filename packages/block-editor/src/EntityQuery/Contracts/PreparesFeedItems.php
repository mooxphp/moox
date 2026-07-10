<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

interface PreparesFeedItems
{
    /**
     * @param  Collection<int, Model>  $models
     */
    public function prepare(Collection $models, string $locale): void;
}
