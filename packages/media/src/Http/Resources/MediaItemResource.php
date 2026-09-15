<?php

declare(strict_types=1);

namespace Moox\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Support\MediaLocaleResolver;

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
        $resolver = app(MediaLocaleResolver::class);
        $metadata = $resolver->mediaMetadata($media->loadMissing('translations'));

        /** @var MediaCollection|null $collection */
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
            'name' => $metadata['name'],
            'title' => $metadata['title'],
            'alt' => $metadata['alt'],
            'collection' => $collection ? [
                'id' => $collection->getKey(),
                'name' => $resolver->collectionName($collection->loadMissing('translations')),
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

            return $url !== '' ? $url : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
