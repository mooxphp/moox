<?php

namespace Moox\Media\Livewire;

use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends Component
{
    public $mediaItems = [];

    public function mount()
    {
        $this->mediaItems = Media::all();
    }

    public function render()
    {
        dd($this->mediaItems);
        // return view('media::forms.components.media-picker', ['mediaItems' => $this->mediaItems]);
    }
}
