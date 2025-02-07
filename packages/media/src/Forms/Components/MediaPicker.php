<?php

namespace Moox\Media\Forms\Components;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use League\Flysystem\UnableToCheckFileExistence;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\BaseFileUpload;

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
