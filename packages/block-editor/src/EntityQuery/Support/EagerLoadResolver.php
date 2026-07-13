<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Support;

use Moox\BlockEditor\EntityQuery\Mapping\FeedItemMapping;
use Moox\BlockEditor\Support\BlockEditorLocale;

final class EagerLoadResolver
{
    /**
     * @param  list<string>  $paths
     * @return array<int|string, mixed>
     */
    public function resolve(array $paths, string $locale): array
    {
        $resolvedLocale = BlockEditorLocale::resolveTranslationLocale($locale);
        $localeCandidates = BlockEditorLocale::localeCandidates($resolvedLocale);

        if ($localeCandidates === []) {
            $localeCandidates = [$resolvedLocale];
        }

        $loads = [];
        $includeTranslationAuthor = false;

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            if ($path === 'translations') {
                continue;
            }

            if ($path === 'translations.author' || str_starts_with($path, 'translations.')) {
                $includeTranslationAuthor = true;

                continue;
            }

            if (preg_match('/^(.+)\.translations$/', $path, $matches) === 1) {
                $relation = $matches[1];
                $loads[$relation] = fn ($query) => $query->with([
                    'translations' => fn ($translationQuery) => $translationQuery->whereIn('locale', $localeCandidates),
                ]);

                continue;
            }

            $loads[] = $path;
        }

        $loads['translations'] = function ($query) use ($localeCandidates, $includeTranslationAuthor, $paths): void {
            $query->whereIn('locale', $localeCandidates);

            if ($includeTranslationAuthor) {
                $nestedRelations = [];

                foreach ($paths as $path) {
                    if (! is_string($path) || ! str_starts_with($path, 'translations.')) {
                        continue;
                    }

                    $nestedRelations[] = substr($path, strlen('translations.'));
                }

                if ($nestedRelations !== []) {
                    $query->with(array_values(array_unique($nestedRelations)));
                } else {
                    $query->with('author');
                }
            }
        };

        return $loads;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function resolveFromMapping(FeedItemMapping $mapping, string $locale, array $additionalPaths = []): array
    {
        return $this->resolve(
            array_values(array_unique(array_merge($mapping->eagerLoadPaths(), $additionalPaths))),
            $locale,
        );
    }
}
