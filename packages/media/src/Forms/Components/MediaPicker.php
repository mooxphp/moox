<?php

namespace Moox\Media\Forms\Components;

use Closure;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    protected array $uploadConfig = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveRelationshipsUsing(function (self $component, $state) {
            /** @var \Illuminate\Database\Eloquent\Model|null $record */
            $record = $component->getRecord();
            if (! $record) {
                return;
            }

            $mediaIds = is_array($state) ? $state : [$state];

            $mediaIds = array_filter($mediaIds, function ($id) {
                return $id !== null && $id !== '';
            });

            MediaUsable::query()
                ->where('media_usable_id', $record->getKey())
                ->where('media_usable_type', get_class($record))
                ->whereNotIn('media_id', $mediaIds)
                ->delete();

            $attachments = [];
            $index = 1;

            foreach ($mediaIds as $mediaId) {
                $media = Media::query()->where('id', $mediaId)->first();

                if (! $media) {
                    continue;
                }

                // @phpstan-ignore-next-line staticMethod.notFound (Eloquent Model::firstOrCreate)
                MediaUsable::firstOrCreate([
                    'media_id' => $media->getKey(),
                    'media_usable_id' => $record->getKey(),
                    'media_usable_type' => get_class($record),
                ]);

                // Get metadata from media_translations (use current locale from record context)
                $metadata = $this->getMediaMetadataFromTranslations($media, $record);

                $attachments[$index] = [
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

    public function getUploadConfig(): array
    {
        return $this->uploadConfig;
    }

    /**
     * Get media metadata from media_translations table
     * Uses default locale first, then en_US, then first available translation
     */
    protected function getMediaMetadataFromTranslations(Media $media, ?\Illuminate\Database\Eloquent\Model $record = null): array
    {
        // Get default locale from Localization
        $defaultLocale = 'en_US';
        if (class_exists(Localization::class)) {
            $localization = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->with('language')
                ->first();

            if ($localization) {
                $defaultLocale = $localization->getAttribute('locale_variant') ?: $localization->language->alpha2;
            }
        }

        // Get translations from media_translations table
        $translations = DB::table('media_translations')
            ->where('media_id', $media->id)
            ->get()
            ->keyBy('locale');

        // Try to get default locale translation first
        $translation = $translations->get($defaultLocale);

        // Fallback to en_US if default locale doesn't exist
        if (! $translation) {
            $translation = $translations->get('en_US');
        }

        // Fallback to first available translation if en_US doesn't exist
        if (! $translation && $translations->isNotEmpty()) {
            $translation = $translations->first();
        }

        return [
            'title' => $translation->title ?? null,
            'alt' => $translation->alt ?? null,
            'description' => $translation->description ?? null,
            'internal_note' => $translation->internal_note ?? null,
        ];
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
