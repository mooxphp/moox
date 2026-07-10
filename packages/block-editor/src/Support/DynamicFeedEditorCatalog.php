<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Support;

use Illuminate\Support\Collection;
use Moox\BlockEditor\EntityQuery\Contracts\EntityQuerySource;
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;
use Moox\BlockEditor\EntityQuery\Support\FilterOptionsResolver;

final class DynamicFeedEditorCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sources(string $locale): array
    {
        $filterOptionsResolver = app(FilterOptionsResolver::class);
        $registeredSources = EntityQuerySourceRegistry::sources();
        $resolvedOptionsByResolver = self::resolveFilterOptionsByResolver($registeredSources, $locale, $filterOptionsResolver);

        return $registeredSources
            ->map(function (EntityQuerySource $source) use ($locale, $resolvedOptionsByResolver): array {
                $filterSchema = $source->filterSchema();
                $filterOptions = [];

                foreach (array_keys($filterSchema) as $filterKey) {
                    $resolver = $filterSchema[$filterKey]['options_resolver'] ?? null;

                    if (is_string($resolver) && $resolver !== '' && array_key_exists($resolver, $resolvedOptionsByResolver)) {
                        $filterOptions[$filterKey] = $resolvedOptionsByResolver[$resolver];

                        continue;
                    }

                    $filterOptions[$filterKey] = $source->filterOptions($filterKey, $locale);
                }

                return [
                    'key' => $source->key(),
                    'label' => $source->label(),
                    'filterSchema' => $filterSchema,
                    'views' => collect($source->views())->map(fn (array $view, string $key): array => [
                        'key' => $key,
                        'label' => $view['label'] ?? $key,
                    ])->values()->all(),
                    'defaultView' => $source->defaultView(),
                    'filterOptions' => $filterOptions,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, EntityQuerySource>  $sources
     * @return array<string, list<array{value: int|string, label: string}>>
     */
    private static function resolveFilterOptionsByResolver(Collection $sources, string $locale, FilterOptionsResolver $resolver): array
    {
        $resolved = [];

        foreach ($sources as $source) {
            foreach ($source->filterSchema() as $schema) {
                if (! is_array($schema)) {
                    continue;
                }

                $resolverKey = $schema['options_resolver'] ?? null;

                if (! is_string($resolverKey) || $resolverKey === '' || array_key_exists($resolverKey, $resolved)) {
                    continue;
                }

                $resolved[$resolverKey] = $resolver->resolve($resolverKey, $locale);
            }
        }

        return $resolved;
    }
}
