<?php

namespace Moox\Media\Forms\Components;

use Closure;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
use Illuminate\Contracts\Support\Arrayable;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    protected array $uploadConfig = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveRelationshipsUsing(function (self $component, $state) {
            $record = $component->getRecord();
            if (!$record) {
                return;
            }

            $mediaIds = is_array($state) ? $state : [$state];

            MediaUsable::where('media_usable_id', $record->id)
                ->where('media_usable_type', get_class($record))
                ->whereNotIn('media_id', $mediaIds)
                ->delete();

            $attachments = [];
            $index = 1;

            foreach ($mediaIds as $mediaId) {
                $media = Media::find($mediaId);

                if (!$media) {
                    continue;
                }

                MediaUsable::firstOrCreate([
                    'media_id' => $media->id,
                    'media_usable_id' => $record->id,
                    'media_usable_type' => get_class($record),
                ]);

                $attachments[$index] = [
                    'file_name' => $media->file_name,
                    'title' => $media->title,
                    'description' => $media->description,
                    'internal_note' => $media->internal_note,
                    'alt' => $media->alt,
                ];

                $index++;
            }

            $statePath = $component->getStatePath();
            $fieldName = last(explode('.', $statePath));

            $columnType = \Schema::getColumnType($record->getTable(), $fieldName);

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

    public function showDownloadButton(Closure|bool|null $showDownloadButton): static
    {
        $this->uploadConfig['show_download_button'] = $showDownloadButton instanceof Closure ? $showDownloadButton() : $showDownloadButton;
        return parent::showDownloadButton($showDownloadButton);
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

}
