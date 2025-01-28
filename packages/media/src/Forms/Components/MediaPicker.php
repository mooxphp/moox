<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    public function getMedia()
    {
        return Media::all();
    }
}
