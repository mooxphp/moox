<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\BlockEditor\Support\BlockEditorLocale;
use Moox\Category\Models\Category;

final class FilterOptionsResolver
{
    /** @var array<string, list<array{value: int|string, label: string}>> */
    private static array $resolvedOptionsCache = [];

    /**
     * @return list<array{value: int|string, label: string}>
     */
    public function resolve(string $resolver, string $locale): array
    {
        $cacheKey = $resolver.':'.BlockEditorLocale::resolveTranslationLocale($locale);

        if (array_key_exists($cacheKey, self::$resolvedOptionsCache)) {
            return self::$resolvedOptionsCache[$cacheKey];
        }

        $resolved = match ($resolver) {
            'category' => $this->resolveCategories($locale),
            default => [],
        };

        self::$resolvedOptionsCache[$cacheKey] = $resolved;

        return $resolved;
    }

    public static function clearCache(): void
    {
        self::$resolvedOptionsCache = [];
    }

    /**
     * @return list<array{value: int|string, label: string}>
     */
    private function resolveCategories(string $locale): array
    {
        if (! class_exists(Category::class)) {
            return [];
        }

        $resolvedLocale = BlockEditorLocale::resolveTranslationLocale($locale);
        $localeCandidates = BlockEditorLocale::localeCandidates($resolvedLocale);

        if ($localeCandidates === []) {
            $localeCandidates = [$resolvedLocale];
        }

        return Category::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($query) use ($localeCandidates): void {
                $query->whereIn('locale', $localeCandidates);
            })
            ->with([
                'translations' => fn ($query) => $query->whereIn('locale', $localeCandidates),
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Model $category) use ($resolvedLocale, $localeCandidates): array {
                $translation = $this->resolveTranslation($category, $resolvedLocale, $localeCandidates);
                $title = is_object($translation) ? (string) ($translation->title ?? '') : '';
                $label = trim($title) !== '' ? $title : 'ID: '.$category->getKey();

                return [
                    'value' => $category->getKey(),
                    'label' => $label,
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $localeCandidates
     */
    private function resolveTranslation(Model $category, string $resolvedLocale, array $localeCandidates): ?object
    {
        if (! method_exists($category, 'translate')) {
            return null;
        }

        $translation = $category->translate($resolvedLocale, false);
        if ($translation !== null) {
            return $translation;
        }

        foreach ($localeCandidates as $candidate) {
            $translation = $category->translate($candidate, false);
            if ($translation !== null) {
                return $translation;
            }
        }

        return null;
    }
}
