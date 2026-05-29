<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Models\MediaCollectionTranslation;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

final class ImportDemoMediaToMediathek
{
    /**
     * Sorted demo image paths — at most $limit entries (one per user to create).
     *
     * @return list<string>
     */
    public static function listImagePaths(?string $sourceDir, int $limit): array
    {
        if ($sourceDir === null || ! is_dir($sourceDir) || $limit < 1) {
            return [];
        }

        $files = [];
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $matched = File::glob($sourceDir.'/*.'.$extension) ?: [];
            foreach ($matched as $path) {
                if (is_string($path) && is_file($path)) {
                    $files[] = $path;
                }
            }
        }
        $files = array_values(array_unique($files));
        sort($files, SORT_NATURAL);

        return array_slice($files, 0, $limit);
    }

    /**
     * Import a single demo file into the mediathek (skips upload if file_hash already exists).
     */
    public static function importFromPath(string $path, ?int $mediaCollectionId = null): ?Media
    {
        if (! class_exists(Media::class) || ! is_file($path)) {
            return null;
        }

        $collection = self::resolveMediaCollection($mediaCollectionId);

        if ($collection === null) {
            return null;
        }

        return self::importFile(
            $path,
            $collection,
            self::resolveCollectionName($collection),
            (string) config('app.locale', 'en_US'),
        );
    }

    /**
     * @return array{id: int, file_name: string, title: string, description: null, internal_note: null, alt: string}
     */
    public static function avatarPayloadFromMedia(Media $media): array
    {
        $title = self::resolveTitle($media);

        return [
            'id' => (int) $media->getKey(),
            'file_name' => (string) $media->file_name,
            'title' => $title,
            'description' => null,
            'internal_note' => null,
            'alt' => $title,
        ];
    }

    public static function avatarUrlFromMedia(Media $media): string
    {
        return json_encode(self::avatarPayloadFromMedia($media), JSON_UNESCAPED_UNICODE);
    }

    private static function importFile(
        string $path,
        MediaCollection $collection,
        string $collectionName,
        string $locale,
    ): ?Media {
        $originalName = basename($path);
        $fileHash = hash_file('sha256', $path);

        if ($fileHash === false) {
            return null;
        }

        $existingMedia = Media::query()
            ->where('custom_properties->file_hash', $fileHash)
            ->first();

        if ($existingMedia instanceof Media) {
            return $existingMedia;
        }

        $mimeType = mime_content_type($path) ?: 'image/jpeg';

        $uploadedFile = new UploadedFile(
            $path,
            $originalName,
            $mimeType,
            null,
            true,
        );

        $model = new Media;
        $model->exists = true;

        /** @var Media $media */
        $media = app(FileAdderFactory::class)
            ->create($model, $uploadedFile)
            ->preservingOriginal()
            ->toMediaCollection($collectionName);

        $media->media_collection_id = $collection->getKey();
        $media->collection_name = $collectionName;
        $media->original_model_type = Media::class;
        $media->original_model_id = $media->getKey();
        $media->model_id = $media->getKey();
        $media->model_type = Media::class;
        $media->setCustomProperty('file_hash', $fileHash);

        if (str_starts_with($media->mime_type, 'image/')) {
            try {
                $mediaPath = $media->getPath();
                if ($mediaPath !== '') {
                    $size = @getimagesize($mediaPath);
                    if (is_array($size)) {
                        $media->setCustomProperty('dimensions', [
                            'width' => (int) $size[0],
                            'height' => (int) $size[1],
                        ]);
                    }
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        $media->save();

        $titleFallback = pathinfo($originalName, PATHINFO_FILENAME);

        $translation = $media->translateOrNew($locale);
        $translation->setAttribute('name', $originalName);
        $translation->setAttribute('title', $titleFallback);
        $translation->setAttribute('alt', $titleFallback);
        $translation->save();

        $media->setAttribute('title', $titleFallback);
        $media->setAttribute('alt', $titleFallback);

        return $media;
    }

    private static function resolveMediaCollection(?int $mediaCollectionId): ?MediaCollection
    {
        MediaCollection::ensureUncategorizedExists();

        if ($mediaCollectionId !== null) {
            $collection = MediaCollection::query()->find($mediaCollectionId);

            if ($collection instanceof MediaCollection) {
                return $collection;
            }
        }

        return MediaCollection::query()->with('translations')->orderBy('id')->first();
    }

    private static function resolveCollectionName(MediaCollection $collection): string
    {
        $locale = (string) config('app.locale', 'en_US');
        $fallback = (string) config('app.fallback_locale', 'en_US');

        foreach ([$locale, $fallback, 'en_US'] as $candidate) {
            $translation = $collection->translate($candidate, false);
            $name = self::translationName($translation);
            if ($name !== null) {
                return $name;
            }
        }

        if ($collection->relationLoaded('translations') && $collection->translations->isNotEmpty()) {
            $name = self::translationName($collection->translations->first());
            if ($name !== null) {
                return $name;
            }
        }

        return (string) $collection->getKey();
    }

    private static function translationName(?\Illuminate\Database\Eloquent\Model $translation): ?string
    {
        if (! $translation instanceof MediaCollectionTranslation) {
            return null;
        }

        $name = $translation->getAttribute('name');
        if (! is_string($name)) {
            return null;
        }

        $trimmed = trim($name);

        return $trimmed !== '' ? $trimmed : null;
    }

    private static function resolveTitle(Media $media): string
    {
        if (is_string($media->title) && trim($media->title) !== '') {
            return trim($media->title);
        }

        return pathinfo((string) $media->file_name, PATHINFO_FILENAME);
    }
}
