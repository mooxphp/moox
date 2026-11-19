<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Models\MediaTranslation;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;
use Throwable;

/**
 * Single write path for library uploads (admin, picker, API, replace).
 *
 * Responsibilities:
 * - MIME/extension allowlist via {@see MediaUploadValidator}
 * - SHA-256 content hash + duplicate detection
 * - Spatie FileAdder self-attach + uploader / original_model wiring
 * - Locale translation defaults + {@see MediaPostProcessor}
 *
 * Not responsible for: authorization, Filament notifications, HTTP status codes,
 * Livewire FilePond state / processedHashes.
 */
final class MediaUploadService
{
    public function __construct(
        private readonly MediaUploadValidator $validator = new MediaUploadValidator,
        private readonly MediaPostProcessor $postProcessor = new MediaPostProcessor,
        private readonly MediaLocaleResolver $localeResolver = new MediaLocaleResolver,
    ) {
    }

    /**
     * Create a new media library item from an uploaded file.
     *
     * @throws Throwable Spatie / IO failures bubble to the caller.
     * @throws ValidationException When the file type is not allowed.
     */
    public function upload(
        UploadedFile|TemporaryUploadedFile $file,
        MediaUploadOptions $options = new MediaUploadOptions,
    ): MediaUploadResult {
        $this->validator->ensureAccepted($file, $options->acceptedFileTypes);

        $realPath = $this->resolveRealPath($file);
        $fileHash = hash_file('sha256', $realPath);
        $fileName = $file->getClientOriginalName();

        if ($fileHash === false) {
            throw new RuntimeException('Unable to hash uploaded file.');
        }

        $lock = Cache::lock('media-upload:'.$fileHash, 15);

        try {
            $lock->block(10);

            $existing = $this->findDuplicate($fileName, $fileHash, $options->scopeQuery);

            if ($existing instanceof Media) {
                return new MediaUploadResult(
                    status: MediaUploadStatus::Duplicate,
                    existingMedia: $existing,
                    fileHash: $fileHash,
                    fileName: $fileName,
                );
            }

            $locale = $this->resolveLocale($options->locale);
            $collectionName = $this->resolveCollectionName($options, $locale);

            $media = $this->localeResolver->withLocale($locale, function () use ($file, $options, $fileHash, $fileName, $collectionName, $locale): Media {
                return $this->persistNewMedia(
                    file: $file,
                    fileHash: $fileHash,
                    fileName: $fileName,
                    collectionName: $collectionName,
                    locale: $locale,
                    options: $options,
                );
            });

            return new MediaUploadResult(
                status: MediaUploadStatus::Created,
                media: $media,
                fileHash: $fileHash,
                fileName: $fileName,
            );
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Replace (or version-edit) an existing media item with a new file.
     *
     * Filenames matching `*-v{n}` are treated as edits: the old row is kept and the new
     * row inherits `original_model_*`. Otherwise usables are moved to the new row and
     * the old media is deleted.
     *
     * @throws Throwable
     * @throws ValidationException
     */
    public function replace(
        Media $oldMedia,
        UploadedFile|TemporaryUploadedFile $file,
        MediaUploadOptions $options = new MediaUploadOptions,
    ): MediaUploadResult {
        if ($oldMedia->getOriginal('write_protected')) {
            throw new RuntimeException('This media item is write-protected.');
        }

        $this->validator->ensureAccepted($file, $options->acceptedFileTypes);

        $realPath = $this->resolveRealPath($file);
        $fileHash = hash_file('sha256', $realPath);
        $fileName = $file->getClientOriginalName();

        if ($fileHash === false) {
            throw new RuntimeException('Unable to hash uploaded file.');
        }

        $lock = Cache::lock('media-upload:'.$fileHash, 15);

        try {
            $lock->block(10);

            $existing = $this->findDuplicate(
                $fileName,
                $fileHash,
                $options->scopeQuery,
                excludeMediaId: $oldMedia->getKey(),
            );

            if ($existing instanceof Media) {
                return new MediaUploadResult(
                    status: MediaUploadStatus::Duplicate,
                    existingMedia: $existing,
                    fileHash: $fileHash,
                    fileName: $fileName,
                );
            }

            $locale = $this->resolveLocale($options->locale);
            $newBaseName = pathinfo($fileName, PATHINFO_FILENAME);
            $isEdit = (bool) preg_match('/-v\d+$/', $newBaseName);
            $collectionName = $oldMedia->collection_name ?: ($options->collectionName ?: __('media::fields.uncategorized'));

            $replaceOptions = $options->with(
                mediaCollectionId: $options->mediaCollectionId ?? $oldMedia->media_collection_id,
                collectionName: $collectionName,
                locale: $locale,
            );

            /** @var Media $media */
            $media = DB::transaction(function () use ($oldMedia, $file, $fileHash, $fileName, $newBaseName, $isEdit, $collectionName, $locale, $replaceOptions): Media {
                $media = $this->persistNewMedia(
                    file: $file,
                    fileHash: $fileHash,
                    fileName: $fileName,
                    collectionName: $collectionName,
                    locale: $locale,
                    options: $replaceOptions,
                    titleOverride: $newBaseName,
                    nameOverride: null,
                    preserveOriginalModelFrom: $isEdit ? $oldMedia : null,
                    cloneTranslationsFrom: $oldMedia,
                );

                if (! $isEdit) {
                    $oldFileName = $oldMedia->file_name;

                    $usables = DB::table('media_usables')
                        ->where('media_id', $oldMedia->getKey())
                        ->get();

                    foreach ($usables as $usable) {
                        DB::table('media_usables')->insert([
                            'media_id' => $media->getKey(),
                            'media_usable_id' => $usable->media_usable_id,
                            'media_usable_type' => $usable->media_usable_type,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    Media::syncMediaMetadata($media->load('translations'), $oldFileName);

                    DB::table('media_usables')
                        ->where('media_id', $oldMedia->getKey())
                        ->delete();

                    $oldMedia->delete();
                }

                return $media;
            });

            return new MediaUploadResult(
                status: $isEdit ? MediaUploadStatus::Edited : MediaUploadStatus::Replaced,
                media: $media,
                fileHash: $fileHash,
                fileName: $fileName,
                replacedMedia: $isEdit ? $oldMedia : null,
            );
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @param  \Closure(Builder<Media>): Builder<Media>|null  $scopeQuery
     */
    public function findDuplicate(
        string $fileName,
        string $fileHash,
        mixed $scopeQuery = null,
        int|string|null $excludeMediaId = null,
    ): ?Media {
        /** @var Builder<Media> $query */
        $query = Media::query();

        if ($scopeQuery instanceof \Closure) {
            $query = $scopeQuery($query);
        }

        if ($excludeMediaId !== null) {
            $query->whereKeyNot($excludeMediaId);
        }

        return $query
            ->where(function (Builder $query) use ($fileName, $fileHash): void {
                $query->whereHas('translations', function (Builder $query) use ($fileName): void {
                    $query->where('name', $fileName);
                })->orWhere(function (Builder $query) use ($fileHash): void {
                    $query->where('custom_properties->file_hash', $fileHash);
                });
            })
            ->first();
    }

    private function persistNewMedia(
        UploadedFile|TemporaryUploadedFile $file,
        string $fileHash,
        string $fileName,
        string $collectionName,
        string $locale,
        MediaUploadOptions $options,
        ?string $titleOverride = null,
        ?string $nameOverride = null,
        ?Media $preserveOriginalModelFrom = null,
        ?Media $cloneTranslationsFrom = null,
    ): Media {
        $model = new Media;
        $model->exists = true;

        $fileAdder = FileAdderFactory::create($model, $file);
        $fileSize = method_exists($file, 'getSize') ? $file->getSize() : null;
        if (is_int($fileSize) && $fileSize > 0) {
            $fileAdder->setFileSize($fileSize);
        }

        /** @var Media $media */
        $media = $fileAdder->preservingOriginal()->toMediaCollection($collectionName);

        $media->media_collection_id = $options->mediaCollectionId;
        $media->collection_name = $collectionName;

        if ($options->scope !== null) {
            $media->scope = $options->scope;
        }

        $uploader = $this->resolveUploader($options);
        $media->uploader_type = $uploader !== null ? $uploader::class : null;
        $media->uploader_id = $uploader?->getAuthIdentifier();

        if ($preserveOriginalModelFrom instanceof Media) {
            $media->original_model_type = $preserveOriginalModelFrom->getAttribute('original_model_type');
            $media->original_model_id = $preserveOriginalModelFrom->getAttribute('original_model_id');
        } else {
            $media->original_model_type = Media::class;
            $media->original_model_id = $media->getKey();
        }

        $media->model_id = $media->getKey();
        $media->model_type = Media::class;
        $media->setCustomProperty('file_hash', $fileHash);

        $this->postProcessor->process($media);

        if ($options->applyDefaults instanceof \Closure) {
            ($options->applyDefaults)($media);
        }

        $media->save();

        $title = $titleOverride ?? pathinfo($fileName, PATHINFO_FILENAME);
        $name = $nameOverride ?? $options->name ?? $fileName;
        $translationTitle = $options->title ?? $title;
        $translationAlt = $options->alt ?? $title;

        $translation = $media->translateOrNew($locale);
        $translation->setAttribute('name', $name);
        $translation->setAttribute('title', $translationTitle);
        $translation->setAttribute('alt', $translationAlt);
        $translation->save();

        if ($cloneTranslationsFrom instanceof Media) {
            $cloneTranslationsFrom->loadMissing('translations');

            foreach ($cloneTranslationsFrom->translations as $oldTranslation) {
                $cloned = $media->translateOrNew($oldTranslation->locale);
                $cloned->setAttribute('name', $media->file_name);
                $cloned->setAttribute('title', $oldTranslation->title ?: $title);
                $cloned->setAttribute('alt', $oldTranslation->alt ?: ($oldTranslation->title ?: $title));
                $cloned->setAttribute('description', $oldTranslation->description);
                $cloned->setAttribute('internal_note', $oldTranslation->internal_note);
            }

            // Prefer stored file_name on the primary locale after clone (replace UX).
            $primary = $media->translateOrNew($locale);
            $primary->setAttribute('name', $media->file_name);
            $primary->setAttribute('title', $options->title ?? $title);
            $primary->setAttribute('alt', $options->alt ?? $title);
        }

        $media->save();

        if ($options->discardOtherLocales) {
            MediaTranslation::query()
                ->where('media_id', $media->getKey())
                ->where('locale', '!=', $locale)
                ->delete();
        }

        return $media->refresh();
    }

    private function resolveRealPath(UploadedFile|TemporaryUploadedFile $file): string
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            throw new RuntimeException('Uploaded file path is not readable.');
        }

        return $path;
    }

    private function resolveLocale(?string $locale): string
    {
        if (is_string($locale) && $locale !== '') {
            return $locale;
        }

        return $this->localeResolver->adminDefaultLocale();
    }

    private function resolveCollectionName(MediaUploadOptions $options, string $locale): string
    {
        if (is_string($options->collectionName) && $options->collectionName !== '') {
            return $options->collectionName;
        }

        if ($options->mediaCollectionId !== null) {
            $collection = MediaCollection::query()
                ->with('translations')
                ->find($options->mediaCollectionId);

            if ($collection instanceof MediaCollection) {
                $name = $this->localeResolver->collectionName($collection, $locale);

                if (filled($name)) {
                    return $name;
                }
            }
        }

        return __('media::fields.uncategorized');
    }

    private function resolveUploader(MediaUploadOptions $options): ?Authenticatable
    {
        if ($options->uploader instanceof Authenticatable) {
            return $options->uploader;
        }

        if (! $options->useAuthenticatedUploader) {
            return null;
        }

        $user = Auth::user();

        return $user instanceof Authenticatable ? $user : null;
    }
}
