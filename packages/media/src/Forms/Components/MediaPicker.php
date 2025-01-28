<?php

namespace Moox\Media\Forms\Components;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    public function getMedia()
    {
        return Media::all();
    }

}



