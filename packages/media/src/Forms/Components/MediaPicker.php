<?php

namespace Moox\Media\Forms\Components;

use Closure;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Support\Scopes\ScopeValue;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
use Moox\Media\Support\MediaLocaleResolver;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    protected array $uploadConfig = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveRelationshipsUsing(function (self $component, $state) {
            /** @var Model|null $record */
            $record = $component->getRecord();
            if (! $record) {
                return;
            }

            $mediaIds = is_array($state) ? $state : [$state];

            $mediaIds = array_values(array_filter($mediaIds, static fn ($id): bool => $id !== null && $id !== ''));
            $authorizedMedia = $component->resolveAuthorizedMedia($record, $mediaIds);
            $authorizedMediaIds = $authorizedMedia->modelKeys();

            if ($mediaIds !== [] && count($authorizedMediaIds) !== count($mediaIds)) {
                throw new AuthorizationException('One or more selected media items are not available for this record.');
            }

            $detachQuery = MediaUsable::query()
                ->where('media_usable_id', $record->getKey())
                ->where('media_usable_type', get_class($record));

            if ($authorizedMediaIds !== []) {
                $detachQuery->whereNotIn('media_id', $authorizedMediaIds);
            }

            $detachQuery->delete();

            $attachments = [];
            $index = 1;

            foreach ($authorizedMedia as $media) {
                MediaUsable::firstOrCreate([
                    'media_id' => $media->getKey(),
                    'media_usable_id' => $record->getKey(),
                    'media_usable_type' => get_class($record),
                ]);

                $metadata = $component->getMediaMetadataFromTranslations($media, $record);

                $attachments[$index] = [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'title' => $metadata['title'],
                    'description' => $metadata['description'],
                    'internal_note' => $metadata['internal_note'],
                    'alt' => $metadata['alt'],
                ];

                $index++;
            }

            $statePath = $component->getStatePath();
            $fieldName = last(explode('.', $statePath));

            $columnType = Schema::getColumnType($record->getTable(), $fieldName);

            if ($columnType === 'json') {
                $record->{$fieldName} = $component->isMultiple() ? $attachments : ($attachments[1] ?? null);
            } else {
                $record->{$fieldName} = json_encode($component->isMultiple() ? $attachments : ($attachments[1] ?? null), JSON_UNESCAPED_UNICODE);
            }

            $record->save();
        });
    }

    public function multiple(Closure|bool $condition = true): static
    {
        $this->uploadConfig['multiple'] = $condition instanceof Closure ? $condition() : $condition;

        return parent::multiple($condition);
    }

    public function acceptedFileTypes(Arrayable|Closure|array $types): static
    {
        $this->uploadConfig['accepted_file_types'] = $types instanceof Closure ? $types() : $types;

        return parent::acceptedFileTypes($types);
    }

    public function maxFiles(Closure|int|null $count): static
    {
        $this->uploadConfig['max_files'] = $count instanceof Closure ? $count() : $count;

        return parent::maxFiles($count);
    }

    public function minFiles(Closure|int|null $count): static
    {
        $this->uploadConfig['min_files'] = $count instanceof Closure ? $count() : $count;

        return parent::minFiles($count);
    }

    public function maxSize(Closure|int|null $size): static
    {
        $this->uploadConfig['max_size'] = $size instanceof Closure ? $size() : $size;

        return parent::maxSize($size);
    }

    public function minSize(Closure|int|null $size): static
    {
        $this->uploadConfig['min_size'] = $size instanceof Closure ? $size() : $size;

        return parent::minSize($size);
    }

    public function imageEditor(Closure|bool $condition = true): static
    {
        $this->uploadConfig['image_editor'] = $condition instanceof Closure ? $condition() : $condition;

        return parent::imageEditor($condition);
    }

    public function imageEditorMode(int $mode): static
    {
        $this->uploadConfig['image_editor_mode'] = $mode;

        return parent::imageEditorMode($mode);
    }

    public function imageEditorViewportWidth(Closure|int|null $width): static
    {
        $this->uploadConfig['image_editor_viewport_width'] = $width instanceof Closure ? $width() : $width;

        return parent::imageEditorViewportWidth($width);
    }

    public function imageEditorViewportHeight(Closure|int|null $height): static
    {
        $this->uploadConfig['image_editor_viewport_height'] = $height instanceof Closure ? $height() : $height;

        return parent::imageEditorViewportHeight($height);
    }

    public function imageEditorAspectRatios(Closure|array $ratios): static
    {
        $this->uploadConfig['image_editor_aspect_ratios'] = $ratios instanceof Closure ? $ratios() : $ratios;

        return parent::imageEditorAspectRatios($ratios);
    }

    public function placeholder(Closure|string|null $placeholder): static
    {
        $this->uploadConfig['placeholder'] = $placeholder instanceof Closure ? $placeholder() : $placeholder;

        return parent::placeholder($placeholder);
    }

    public function panelLayout(Closure|string|null $layout): static
    {
        $this->uploadConfig['panel_layout'] = $layout instanceof Closure ? $layout() : $layout;

        return parent::panelLayout($layout);
    }

    public function disk(Closure|string|null $disk): static
    {
        $this->uploadConfig['disk'] = $disk instanceof Closure ? $disk() : $disk;

        return parent::disk($disk);
    }

    public function directory(Closure|string|null $directory): static
    {
        $this->uploadConfig['directory'] = $directory instanceof Closure ? $directory() : $directory;

        return parent::directory($directory);
    }

    public function visibility(Closure|string|null $visibility): static
    {
        $this->uploadConfig['visibility'] = $visibility instanceof Closure ? $visibility() : $visibility;

        return parent::visibility($visibility);
    }

    public function scopedMediaCollectionId(Closure|int|null $mediaCollectionId): static
    {
        $this->uploadConfig['scoped_media_collection_id'] = $mediaCollectionId;

        return $this;
    }

    public function scopedMediaScope(Closure|string|null $scope): static
    {
        $this->uploadConfig['scoped_media_scope'] = $scope;

        return $this;
    }

    public function getUploadConfig(): array
    {
        return array_map(
            fn (mixed $value): mixed => $this->evaluate($value),
            $this->uploadConfig,
        );
    }

    /**
     * @param  array<int, mixed>  $mediaIds
     * @return EloquentCollection<int, Media>
     */
    protected function resolveAuthorizedMedia(Model $record, array $mediaIds): EloquentCollection
    {
        if ($mediaIds === []) {
            return new EloquentCollection;
        }

        $query = Media::query()->whereIn('id', $mediaIds);

        $scopedMediaCollectionId = $this->resolveScopedMediaCollectionId();
        if ($scopedMediaCollectionId !== null) {
            $query->where('media_collection_id', $scopedMediaCollectionId);
        }

        $scopedMediaScope = $this->resolveScopedMediaScope($record);
        if (filled($scopedMediaScope)) {
            $query->where('scope', $scopedMediaScope);
        }

        return $query
            ->get()
            ->sortBy(static function (Media $media) use ($mediaIds): int {
                $position = array_search($media->getKey(), $mediaIds, false);

                return $position === false ? PHP_INT_MAX : $position;
            })
            ->values();
    }

    protected function resolveScopedMediaCollectionId(): ?int
    {
        $scopedMediaCollectionId = $this->getUploadConfig()['scoped_media_collection_id'] ?? null;

        if ($scopedMediaCollectionId === null || $scopedMediaCollectionId === '') {
            return null;
        }

        return (int) $scopedMediaCollectionId;
    }

    protected function resolveScopedMediaScope(?Model $record = null): ?string
    {
        $scopedMediaScope = $this->getUploadConfig()['scoped_media_scope'] ?? null;

        if (filled($scopedMediaScope)) {
            return ScopeValue::toStringOrNull((string) $scopedMediaScope);
        }

        if (! $record) {
            return null;
        }

        if (method_exists($record, 'deriveChildScope')) {
            return $record->deriveChildScope('media');
        }

        if (method_exists($record, 'deriveScopeForOrigin')) {
            return $record->deriveScopeForOrigin('media');
        }

        return ScopeValue::forOriginString($record->getAttribute('scope'), 'media');
    }

    /**
     * Get media metadata from media_translations table
     * Uses default locale first, then en_US, then first available translation
     */
    protected function getMediaMetadataFromTranslations(Media $media, ?Model $record = null): array
    {
        return app(MediaLocaleResolver::class)->mediaMetadata($media);
    }

    /**
     * Safely get an attribute from the Media model
     */
    protected function getMediaAttribute(Media $media, string $attribute): ?string
    {
        if (isset($media->{$attribute})) {
            return $media->{$attribute};
        }

        $value = $media->getAttribute($attribute);
        if ($value !== null) {
            return $value;
        }

        return null;
    }
}
