<?php

declare(strict_types=1);

namespace Moox\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;

/**
 * @property Media $resource
 */
class MediaItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Media $media */
        $media = $this->resource;

        $mimeType = (string) ($media->mime_type ?? '');
        $type = $this->typeFromMime($mimeType);

        $name = $this->translatedValue($media, 'name');
        $title = $this->translatedValue($media, 'title');
        $alt = $this->translatedValue($media, 'alt');

        $collection = $media->relationLoaded('collection') ? $media->collection : $media->collection()->first();

        return [
            'id' => $media->getKey(),
            'url' => $media->getUrl(),
            'thumbnail_url' => $this->safeConversionUrl($media, 'thumbnail'),
            'preview_url' => $this->safeConversionUrl($media, 'preview'),
            'poster_url' => $type === 'video' ? ($this->safeConversionUrl($media, 'preview') ?? $this->safeConversionUrl($media, 'thumbnail')) : null,
            'file_name' => $media->file_name,
            'mime_type' => $mimeType !== '' ? $mimeType : null,
            'type' => $type,
            'name' => $name,
            'title' => $title,
            'alt' => $alt,
            'collection' => $collection ? [
                'id' => $collection->getKey(),
                'name' => $this->translatedValue($collection, 'name'),
            ] : null,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    protected function typeFromMime(string $mimeType): string
    {
        $prefix = strtolower(strtok($mimeType, '/')) ?: '';

        return match ($prefix) {
            'image' => 'image',
            'video' => 'video',
            'application', 'text', 'model' => 'document',
            default => 'other',
        };
    }

    protected function safeConversionUrl(Media $media, string $conversion): ?string
    {
        try {
            $url = $media->getUrl($conversion);

            return is_string($url) && $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  Media|MediaCollection  $model
     */
    protected function translatedValue(object $model, string $key): ?string
    {
        $locales = $this->getLocaleFallbackChain();

        foreach ($locales as $locale) {
            if (! method_exists($model, 'translate')) {
                break;
            }

            $translation = $model->translate($locale, false);

            if ($translation && isset($translation->{$key}) && is_string($translation->{$key}) && trim($translation->{$key}) !== '') {
                return $translation->{$key};
            }
        }

        if (method_exists($model, 'translations') && $model->relationLoaded('translations')) {
            $first = $model->translations->first();
            if ($first && isset($first->{$key}) && is_string($first->{$key}) && trim($first->{$key}) !== '') {
                return $first->{$key};
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function getLocaleFallbackChain(): array
    {
        $locales = array_filter([
            app()->getLocale(),
            config('app.fallback_locale'),
            'en_US',
        ], static fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        $expanded = [];
        foreach ($locales as $locale) {
            $expanded[] = $locale;

            $expanded[] = str_replace('-', '_', $locale);
            $expanded[] = str_replace('_', '-', $locale);

            $base = preg_split('/[-_]/', $locale)[0] ?? null;
            if (is_string($base) && $base !== '') {
                $expanded[] = $base;
            }
        }

        return array_values(array_unique(array_filter($expanded, static fn (mixed $value): bool => is_string($value) && trim($value) !== '')));
    }
}
