<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Sources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Moox\BlockEditor\EntityQuery\Contracts\EntityQuerySource;
use Moox\BlockEditor\EntityQuery\Contracts\ConfigurableFeedItemMapper;
use Moox\BlockEditor\EntityQuery\Contracts\FeedItemMapper;
use Moox\BlockEditor\EntityQuery\Contracts\PreparesFeedItems;
use Moox\BlockEditor\EntityQuery\EntityQueryBuilder;
use Moox\BlockEditor\EntityQuery\EntityQueryDefinition;
use Moox\BlockEditor\EntityQuery\Mapping\DraftFeedItemResolver;
use Moox\BlockEditor\EntityQuery\Mapping\FeedItemMapping;
use Moox\BlockEditor\EntityQuery\Mappers\DraftFeedItemMapper;
use Moox\BlockEditor\EntityQuery\Support\EagerLoadResolver;
use Moox\BlockEditor\EntityQuery\Support\FilterOptionsResolver;

final class ConfigDraftEntityQuerySource implements EntityQuerySource
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly string $key,
        private readonly array $config,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        $label = $this->config['label'] ?? $this->key;

        if (! is_string($label)) {
            return $this->key;
        }

        if (str_starts_with($label, 'trans//')) {
            return __(Str::after($label, 'trans//'));
        }

        return $label;
    }

    public function views(): array
    {
        $views = $this->config['views'] ?? [];

        return is_array($views) ? $views : [];
    }

    public function filterSchema(): array
    {
        $schema = $this->config['filter_schema'] ?? [];

        return is_array($schema) ? $schema : [];
    }

    public function sortableColumns(): array
    {
        $columns = $this->config['sortable_columns'] ?? [];

        return is_array($columns) ? $columns : [];
    }

    public function defaultView(): string
    {
        $views = $this->views();
        $default = $this->config['default_view'] ?? array_key_first($views);

        return is_string($default) ? $default : (string) array_key_first($views);
    }

    public function query(EntityQueryDefinition $definition): Collection
    {
        if (! ($this->config['enabled'] ?? true)) {
            return collect();
        }

        $modelClass = $this->config['model'] ?? null;

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            Log::warning('Dynamic feed source model is missing or invalid.', [
                'source' => $this->key,
                'model' => $modelClass,
            ]);

            return collect();
        }

        $mapper = $this->resolveMapper();

        if ($mapper === null) {
            return collect();
        }

        $mapping = $this->feedItemMapping();

        $eagerLoads = app(EagerLoadResolver::class)->resolveFromMapping(
            $mapping,
            $definition->locale,
            $this->configuredEagerLoadPaths(),
        );

        $items = app(EntityQueryBuilder::class)
            ->for($modelClass, $definition)
            ->withDraftDefaults($definition->locale)
            ->applyFilters($this->filterSchema(), $definition->filters)
            ->applySort($this->sortableColumns(), $definition)
            ->limit($definition->limit)
            ->withEagerLoads($eagerLoads)
            ->get();

        if ($mapper instanceof PreparesFeedItems) {
            $mapper->prepare($items, $definition->locale);
        }

        return $items->map(fn (Model $model): array => $mapper->map($model, $definition->locale));
    }

    public function filterOptions(string $filter, string $locale): array
    {
        $schema = $this->filterSchema()[$filter] ?? null;

        if (! is_array($schema)) {
            return [];
        }

        $resolver = $schema['options_resolver'] ?? null;

        if (! is_string($resolver) || $resolver === '') {
            return [];
        }

        return app(FilterOptionsResolver::class)->resolve($resolver, $locale);
    }

    private function resolveMapper(): ?FeedItemMapper
    {
        $mapping = $this->feedItemMapping();

        $mapperClass = $this->config['feed_item_mapper'] ?? DraftFeedItemMapper::class;

        if ($mapperClass === DraftFeedItemMapper::class) {
            return new DraftFeedItemMapper(
                app(DraftFeedItemResolver::class),
                $mapping,
            );
        }

        if (! is_string($mapperClass) || ! class_exists($mapperClass)) {
            Log::warning('Dynamic feed mapper is missing or invalid.', [
                'source' => $this->key,
                'mapper' => $mapperClass,
            ]);

            return null;
        }

        $mapper = app($mapperClass);

        if ($mapper instanceof ConfigurableFeedItemMapper) {
            return $mapper->withMapping($mapping);
        }

        if (! $mapper instanceof FeedItemMapper) {
            Log::warning('Dynamic feed mapper does not implement FeedItemMapper.', [
                'source' => $this->key,
                'mapper' => $mapperClass,
            ]);

            return null;
        }

        return $mapper;
    }

    private function feedItemMapping(): FeedItemMapping
    {
        return FeedItemMapping::fromConfig(
            is_array($this->config['feed_item_mapping'] ?? null)
                ? $this->config['feed_item_mapping']
                : []
        );
    }

    /**
     * @return list<string>
     */
    private function configuredEagerLoadPaths(): array
    {
        $paths = $this->config['eager_load'] ?? [];

        if (! is_array($paths)) {
            return [];
        }

        return array_values(array_filter(
            $paths,
            static fn (mixed $path): bool => is_string($path) && trim($path) !== ''
        ));
    }
}
