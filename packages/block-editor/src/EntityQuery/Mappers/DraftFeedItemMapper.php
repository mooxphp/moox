<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Mappers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\BlockEditor\EntityQuery\Contracts\ConfigurableFeedItemMapper;
use Moox\BlockEditor\EntityQuery\Contracts\PreparesFeedItems;
use Moox\BlockEditor\EntityQuery\Mapping\DraftFeedItemResolver;
use Moox\BlockEditor\EntityQuery\Mapping\FeedItemMapping;

final class DraftFeedItemMapper implements ConfigurableFeedItemMapper, PreparesFeedItems
{
    public function __construct(
        private readonly DraftFeedItemResolver $resolver,
        private FeedItemMapping $mapping,
    ) {}

    public function withMapping(FeedItemMapping $mapping): self
    {
        return new self($this->resolver, $mapping);
    }

    /**
     * @param  Collection<int, Model>  $models
     */
    public function prepare(Collection $models, string $locale): void
    {
        unset($locale);

        $this->resolver->prepare($models, $this->mapping);
    }

    public function map(Model $model, string $locale): array
    {
        $item = $this->resolver->resolve($model, $locale, $this->mapping);

        return $item?->toArray() ?? [];
    }
}
