<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery\Mapping\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\BlockEditor\EntityQuery\Mapping\FeedItemMapping;
use Moox\BlockEditor\Support\BlockEditorLocale;
use Moox\Media\Models\Media;

final class FeedItemRelationResolver
{
    /** @var Collection<int, Media> */
    private Collection $mediaById;

    public function __construct()
    {
        $this->mediaById = collect();
    }

    /**
     * @param  Collection<int, Model>  $models
     */
    public function prepare(Collection $models, FeedItemMapping $mapping): void
    {
        $this->mediaById = collect();

        if (! class_exists(Media::class)) {
            return;
        }

        $mediaIds = $models
            ->flatMap(function (Model $model) use ($mapping): array {
                $ids = [];

                foreach ($mapping->relations as $relation) {
                    if ($relation->type !== 'attribute' || ! $relation->resolveUrl) {
                        continue;
                    }

                    $payload = data_get($model, $relation->path !== '' ? $relation->path : $relation->name);
                    $mediaId = is_array($payload) ? ($payload['id'] ?? null) : null;

                    if (is_numeric($mediaId)) {
                        $ids[] = (int) $mediaId;
                    }
                }

                return $ids;
            })
            ->unique()
            ->values();

        if ($mediaIds->isEmpty()) {
            return;
        }

        $this->mediaById = Media::query()
            ->whereIn('id', $mediaIds)
            ->get()
            ->keyBy(static fn (Media $media): int => (int) $media->getKey());
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(Model $model, ?object $translation, string $locale, FeedItemMapping $mapping): array
    {
        $resolved = [];

        foreach ($mapping->relations as $relation) {
            $value = match ($relation->type) {
                'taxonomy' => $this->resolveTaxonomy($model, $relation, $locale),
                'translation_relation' => $this->resolveTranslationRelation($translation, $relation),
                'attribute' => $this->resolveAttribute($model, $relation),
                default => null,
            };

            if ($value === null) {
                continue;
            }

            $resolved[$relation->output] = $value;

            if ($relation->type === 'attribute' && $relation->resolveUrl && is_array($value)) {
                $resolved['image_url'] = $this->resolveImageUrl($value);
            }
        }

        return $resolved;
    }

    /**
     * @return list<string>
     */
    private function resolveTaxonomy(Model $model, FeedItemRelationDefinition $relation, string $locale): array
    {
        if (! method_exists($model, 'taxonomy')) {
            return [];
        }

        $localeCandidates = BlockEditorLocale::localeCandidates($locale);
        $categories = $model->relationLoaded($relation->name)
            ? $model->getRelation($relation->name)
            : $model->taxonomy($relation->name)
                ->with([
                    'translations' => fn ($query) => $query->whereIn('locale', $localeCandidates),
                ])
                ->get();

        return $categories
            ->map(function (Model $category) use ($localeCandidates, $locale, $relation): string {
                $translation = method_exists($category, 'translate')
                    ? $category->translate($locale, false)
                    : null;

                if ($translation === null && method_exists($category, 'translate')) {
                    foreach ($localeCandidates as $candidate) {
                        $translation = $category->translate($candidate, false);
                        if ($translation !== null) {
                            break;
                        }
                    }
                }

                $title = is_object($translation)
                    ? (string) data_get($translation, $relation->labelAttribute, '')
                    : '';

                return trim($title) !== '' ? $title : 'ID: '.$category->getKey();
            })
            ->values()
            ->all();
    }

    private function resolveTranslationRelation(?object $translation, FeedItemRelationDefinition $relation): ?string
    {
        if ($translation === null) {
            return null;
        }

        $related = data_get($translation, $relation->name);

        if (! is_object($related)) {
            return null;
        }

        foreach ($relation->attributes as $attribute) {
            $value = data_get($related, $attribute);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAttribute(Model $model, FeedItemRelationDefinition $relation): array
    {
        $path = $relation->path !== '' ? $relation->path : $relation->name;
        $value = data_get($model, $path);

        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<string, mixed>  $image
     */
    private function resolveImageUrl(array $image): ?string
    {
        $mediaId = $image['id'] ?? null;

        if (! is_numeric($mediaId)) {
            return null;
        }

        return $this->mediaById->get((int) $mediaId)?->getUrl();
    }
}
