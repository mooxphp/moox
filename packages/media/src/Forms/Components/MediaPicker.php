<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (MediaPicker $component, ?Model $record) {
            if ($record) {
                $component->state([
                    'modelId' => $record->id,
                    'modelClass' => get_class($record),
                ]);
            }
        });

        $this->saveRelationshipsUsing(function (MediaPicker $component) {
            $component->saveUploadedFiles();
            $component->getRecord()->save();
        });
    }
}
