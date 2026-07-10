<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Support;

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
        $includeTranslationAuthor = in_array('translations.author', $paths, true);

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            if ($path === 'translations' || $path === 'translations.author') {
                continue;
            }

            if ($path === 'category.translations') {
                $loads['category'] = fn ($query) => $query->with([
                    'translations' => fn ($translationQuery) => $translationQuery->whereIn('locale', $localeCandidates),
                ]);

                continue;
            }

            $loads[] = $path;
        }

        $loads['translations'] = function ($query) use ($localeCandidates, $includeTranslationAuthor): void {
            $query->whereIn('locale', $localeCandidates);

            if ($includeTranslationAuthor) {
                $query->with('author');
            }
        };

        return $loads;
    }
}
