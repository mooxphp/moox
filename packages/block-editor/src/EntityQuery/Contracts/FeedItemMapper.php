<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Contracts;

use Illuminate\Database\Eloquent\Model;

interface FeedItemMapper
{
    /**
     * @return array{
     *     id: int|string,
     *     title: string,
     *     slug: string,
     *     permalink: string,
     *     description: string,
     *     excerpt: string,
     *     description_plain: string,
     *     excerpt_plain: string,
     *     published_at: \Carbon\CarbonInterface|null,
     *     image: array<string, mixed>,
     *     image_url: string|null,
     *     author_name: string|null,
     *     categories: list<string>,
     *     ...
     * }
     */
    public function map(Model $model, string $locale): array;
}
