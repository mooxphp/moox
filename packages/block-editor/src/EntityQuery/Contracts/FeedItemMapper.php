<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Contracts;

use Illuminate\Database\Eloquent\Model;

interface FeedItemMapper
{
    /**
     * @return array<string, mixed>
     */
    public function map(Model $model, string $locale): array;
}
