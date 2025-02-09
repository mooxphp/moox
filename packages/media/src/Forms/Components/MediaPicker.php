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

            $media->update([
                'model_id' => $record->id,
                'model_type' => get_class($record),
            ]);

            $image = $media->getUrl();
            $component->state($image);
        });
    }
}
