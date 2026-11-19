<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Moox\Media\Models\Media;

/**
 * Call-site configuration for {@see MediaUploadService}.
 *
 * Keep Filament / HTTP concerns out of these options — only data needed to persist media.
 */
final class MediaUploadOptions
{
    /**
     * @param  (Closure(Builder<Media>): Builder<Media>)|null  $scopeQuery  Narrows duplicate detection (e.g. MediaResource::scopeQuery).
     * @param  (Closure(Media): void)|null  $applyDefaults  Mutates the new Media before final save (e.g. applyScopedDefaults).
     * @param  array<int, string>|null  $acceptedFileTypes  MIME/extension allowlist; null → resource config.
     * @param  bool  $discardOtherLocales  When true, delete translation rows for locales other than $locale (picker behaviour).
     */
    public function __construct(
        public readonly ?int $mediaCollectionId = null,
        public readonly ?string $collectionName = null,
        public readonly ?string $locale = null,
        public readonly ?string $scope = null,
        public readonly ?Closure $scopeQuery = null,
        public readonly ?Closure $applyDefaults = null,
        public readonly ?string $name = null,
        public readonly ?string $title = null,
        public readonly ?string $alt = null,
        public readonly ?array $acceptedFileTypes = null,
        public readonly bool $discardOtherLocales = false,
        public readonly ?Authenticatable $uploader = null,
        public readonly bool $useAuthenticatedUploader = true,
    ) {
    }

    /**
     * @param  (Closure(Builder<Media>): Builder<Media>)|null  $scopeQuery
     * @param  (Closure(Media): void)|null  $applyDefaults
     * @param  array<int, string>|null  $acceptedFileTypes
     */
    public function with(
        ?int $mediaCollectionId = null,
        ?string $collectionName = null,
        ?string $locale = null,
        ?string $scope = null,
        ?Closure $scopeQuery = null,
        ?Closure $applyDefaults = null,
        ?string $name = null,
        ?string $title = null,
        ?string $alt = null,
        ?array $acceptedFileTypes = null,
        ?bool $discardOtherLocales = null,
        ?Authenticatable $uploader = null,
        ?bool $useAuthenticatedUploader = null,
    ): self {
        return new self(
            mediaCollectionId: $mediaCollectionId ?? $this->mediaCollectionId,
            collectionName: $collectionName ?? $this->collectionName,
            locale: $locale ?? $this->locale,
            scope: $scope ?? $this->scope,
            scopeQuery: $scopeQuery ?? $this->scopeQuery,
            applyDefaults: $applyDefaults ?? $this->applyDefaults,
            name: $name ?? $this->name,
            title: $title ?? $this->title,
            alt: $alt ?? $this->alt,
            acceptedFileTypes: $acceptedFileTypes ?? $this->acceptedFileTypes,
            discardOtherLocales: $discardOtherLocales ?? $this->discardOtherLocales,
            uploader: $uploader ?? $this->uploader,
            useAuthenticatedUploader: $useAuthenticatedUploader ?? $this->useAuthenticatedUploader,
        );
    }
}
