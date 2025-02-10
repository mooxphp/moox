<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Moox\Media\Models\Media;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveRelationshipsUsing(function (self $component, $state) {
            $record = $component->getRecord();
            if (! $record) {
                return;
            }

            if (is_array($state)) {
                $state = reset($state);
            }

            if (! $state) {
                return;
            }

            $media = Media::find($state);

            if (! $media) {
                return;
            }

            if ($media->model_id && $media->model_type) {
                if ($media->model_id != $record->id || $media->model_type != get_class($record)) {
                    Media::create([
                        'model_id' => $record->id,
                        'model_type' => get_class($record),
                        'original_model_id' => $media->model_id,
                        'original_model_type' => $media->model_type,
                        'collection_name' => $media->collection_name,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'disk' => $media->disk,
                        'conversions_disk' => $media->conversions_disk,
                        'size' => $media->size,
                        'manipulations' => $media->manipulations,
                        'custom_properties' => $media->custom_properties,
                        'generated_conversions' => $media->generated_conversions,
                        'responsive_images' => $media->responsive_images,
                        'order_column' => $media->order_column,

                    ]);
                } else {
                    $component->state($media->uuid);

                    return;
                }
            } else {
                $media->update([
                    'model_id' => $record->id,
                    'model_type' => get_class($record),
                ]);
            }

            $component->state($media->uuid);

            $statePath = $component->getStatePath();
            $fieldName = last(explode('.', $statePath));

            $record->{$fieldName} = $media->getUrl();

            $record->save();
        });
    }
}
