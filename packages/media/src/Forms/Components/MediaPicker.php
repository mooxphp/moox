<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

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
                // Der Media-Eintrag ist bereits einem Modell zugeordnet, also erstellen wir nur einen neuen MediaUsable.
                MediaUsable::create([
                    'media_id' => $media->id,
                    'media_usable_id' => $record->id,
                    'media_usable_type' => get_class($record),
                ]);
            } else {
                // Wenn das Bild noch nicht zugeordnet ist, setze die model_id und model_type in der Media-Tabelle
                $media->model_id = $record->id;
                $media->model_type = get_class($record);
                $media->save();

                // Erstelle den MediaUsable-Eintrag
                MediaUsable::create([
                    'media_id' => $media->id,
                    'media_usable_id' => $record->id,
                    'media_usable_type' => get_class($record),
                ]);
            }

            $statePath = $component->getStatePath();
            $fieldName = last(explode('.', $statePath));

            $record->{$fieldName} = $media->file_name;

            $record->save();
        });
    }
}
