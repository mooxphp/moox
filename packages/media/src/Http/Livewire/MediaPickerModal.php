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
            ->where(function ($query) {
                $query->whereNull('model_id')
                    ->whereNull('model_type');
            })
            ->orWhere(function ($query) {
                $query->whereColumn('model_id', 'original_model_id')
                    ->whereColumn('model_type', 'original_model_type');
            })
            ->orderBy('created_at', 'desc')
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
