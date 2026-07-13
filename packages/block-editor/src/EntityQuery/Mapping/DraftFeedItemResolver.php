<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Mapping;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Moox\BlockEditor\EntityQuery\Data\FeedItem;
use Moox\BlockEditor\EntityQuery\Mapping\Relations\FeedItemRelationResolver;
use Moox\BlockEditor\Support\BlockEditorLocale;
use Moox\Media\Models\Media;

final class DraftFeedItemResolver
{
    public function __construct(
        private readonly FeedItemRelationResolver $relationResolver,
    ) {}

    /**
     * @param  Collection<int, Model>  $models
     */
    public function prepare(Collection $models, FeedItemMapping $mapping): void
    {
        if (! class_exists(Media::class) || ! Schema::hasTable((new Media)->getTable())) {
            return;
        }

        $this->relationResolver->prepare($models, $mapping);
    }

    public function resolve(Model $model, string $locale, FeedItemMapping $mapping): ?FeedItem
    {
        if (! method_exists($model, 'translate')) {
            return null;
        }

        $resolvedLocale = BlockEditorLocale::resolveTranslationLocale($locale);
        $translation = $this->resolveTranslation($model, $resolvedLocale);
        $fields = $mapping->resolvedTranslationFields();

        $description = (string) $this->readTranslationValue($translation, $fields['description'] ?? 'description');
        $excerpt = (string) $this->readTranslationValue($translation, $fields['excerpt'] ?? 'excerpt');
        $title = trim((string) $this->readTranslationValue($translation, $fields['title'] ?? 'title'));

        if ($title === '') {
            $title = $this->resolveFallbackTitle($translation, $mapping, $description, $excerpt);
        }

        $relationValues = $this->relationResolver->resolve($model, $translation, $resolvedLocale, $mapping);
        $descriptionPlain = trim(strip_tags($description));
        $excerptPlain = trim(strip_tags($excerpt));

        $image = is_array($relationValues['image'] ?? null) ? $relationValues['image'] : [];
        $categories = is_array($relationValues['categories'] ?? null) ? $relationValues['categories'] : [];

        return new FeedItem(
            id: $model->getKey(),
            title: $title !== '' ? $title : $mapping->untitledLabel,
            slug: (string) $this->readTranslationValue($translation, $fields['slug'] ?? 'slug'),
            permalink: (string) $this->readTranslationValue($translation, $fields['permalink'] ?? 'permalink'),
            description: $description,
            excerpt: $excerpt,
            descriptionPlain: $descriptionPlain,
            excerptPlain: $excerptPlain,
            publishedAt: $this->resolvePublishedAt($translation, $fields['published_at'] ?? 'published_at'),
            image: $image,
            imageUrl: is_string($relationValues['image_url'] ?? null) ? $relationValues['image_url'] : null,
            authorName: is_string($relationValues['author_name'] ?? null) ? $relationValues['author_name'] : null,
            categories: $categories,
            extra: $this->resolveExtraFields($model, $translation, $mapping),
        );
    }

    private function resolveTranslation(Model $model, string $locale): ?object
    {
        $translation = $model->translate($locale, false);
        if ($translation !== null) {
            return $translation;
        }

        foreach (BlockEditorLocale::localeCandidates($locale) as $candidate) {
            $translation = $model->translate($candidate, false);
            if ($translation !== null) {
                return $translation;
            }
        }

        return null;
    }

    private function resolveFallbackTitle(
        ?object $translation,
        FeedItemMapping $mapping,
        string $description,
        string $excerpt,
    ): string {
        foreach ($mapping->fallbackTitleFrom as $field) {
            $value = match ($field) {
                'excerpt' => trim(strip_tags($excerpt)),
                'description' => trim(strip_tags($description)),
                default => trim(strip_tags((string) $this->readTranslationValue($translation, $field))),
            };

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function readTranslationValue(?object $translation, string $attribute): mixed
    {
        if ($translation === null) {
            return null;
        }

        return data_get($translation, $attribute);
    }

    private function resolvePublishedAt(?object $translation, string $attribute): ?CarbonInterface
    {
        $value = $this->readTranslationValue($translation, $attribute);

        return $value instanceof CarbonInterface ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveExtraFields(Model $model, ?object $translation, FeedItemMapping $mapping): array
    {
        $extra = [];

        foreach ($mapping->extra as $outputKey => $source) {
            $extra[$outputKey] = $this->resolveFieldSource($source, $model, $translation);
        }

        return $extra;
    }

    private function resolveFieldSource(string $source, Model $model, ?object $translation): mixed
    {
        if (str_starts_with($source, 'translation:')) {
            return $this->readTranslationValue($translation, substr($source, 12));
        }

        if (str_starts_with($source, 'model:')) {
            return data_get($model, substr($source, 6));
        }

        return data_get($translation, $source) ?? data_get($model, $source);
    }
}
