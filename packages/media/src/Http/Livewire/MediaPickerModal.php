<?php

namespace Moox\Media\Http\Livewire;

use Livewire\Component;
use Moox\Media\Models\Media;

class MediaPickerModal extends Component
{
    public ?int $modelId = null;

    public ?string $modelClass = null;

    public $media = [];

    public array $selectedMediaIds = [];

    protected $listeners = [
        'set-media-picker-model' => 'setModel',
        'mediaUploaded' => 'refreshMedia',
    ];

    public function setModel(int $modelId, string $modelClass)
    {
        $this->modelId = $modelId;
        $this->modelClass = $modelClass;
        $this->refreshMedia();
    }

    public function refreshMedia()
    {
        $this->media = Media::query()
            ->orderBy('created_at', 'desc')  // Optional: Nach Erstellungsdatum sortieren
            ->get();
    }

    public function toggleMediaSelection(int $mediaId)
    {
        if (in_array($mediaId, $this->selectedMediaIds)) {
            $this->selectedMediaIds = array_diff($this->selectedMediaIds, [$mediaId]);
        } else {
            $this->selectedMediaIds[] = $mediaId;
        }
    }

    public function applySelection()
    {
        $selectedMediaId = $this->selectedMediaIds[0] ?? null;

        if ($selectedMediaId) {
            $media = Media::find($selectedMediaId);

            if ($media) {
                $imageUrl = $media->getUrl();

                $this->dispatch('mediaSelected', ['id' => $selectedMediaId, 'url' => $imageUrl]);
            }
        }

        $this->dispatch('close-modal', id: 'mediaPickerModal');
    }

    public function render()
    {
        return view('media::livewire.media-picker-modal', [
            'media' => $this->media,
        ]);
    }
}
