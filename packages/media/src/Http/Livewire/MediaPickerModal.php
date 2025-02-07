<?php

namespace Moox\Media\Http\Livewire;

use Livewire\Component;
use Moox\Media\Models\Media;

class MediaPickerModal extends Component
{
    public int $modelId;

    public string $modelClass;

    public $media = [];

    public array $selectedMedia = [];

    protected $listeners = ['set-media-picker-model' => 'setModel', 'mediaUploaded' => 'refreshMedia'];

    public function setModel(int $modelId, string $modelClass)
    {
        $this->modelId = $modelId;
        $this->modelClass = $modelClass;
        $this->refreshMedia();
    }

    public function refreshMedia()
    {
        $this->media = Media::where(function ($query) {
            if ($this->modelId && class_exists($this->modelClass)) {
                $query->where('model_id', $this->modelId)
                    ->where('model_type', $this->modelClass);
            }
        })
            ->orWhereNull('model_id')
            ->orWhereNull('model_type')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function toggleSelection($mediaId)
    {
        if (in_array($mediaId, $this->selectedMedia)) {
            $this->selectedMedia = array_diff($this->selectedMedia, [$mediaId]);
        } else {
            $this->selectedMedia[] = $mediaId;
        }
    }

    public function saveSelectedMedia()
    {
        if (empty($this->selectedMedia)) {
            return;
        }

        $selectedMediaUrls = Media::whereIn('id', $this->selectedMedia)
            ->get()
            ->map(fn ($media) => $media->getUrl())
            ->values()
            ->toArray();

        $this->dispatch('mediaSelected', selectedMediaUrls: $selectedMediaUrls);

        $this->dispatch('close-modal', id: 'mediaPickerModal');

        $this->selectedMedia = [];
    }

    public function render()
    {
        return view('media::livewire.media-picker-modal', [
            'media' => $this->media,
        ]);
    }
}
