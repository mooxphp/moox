<?php

namespace Moox\Media\Http\Livewire;

use Livewire\Component;
use Moox\Media\Models\Media;

class MediaPickerModal extends Component
{
    public ?int $modelId = null;

    public ?string $modelClass = null;

    public array $media = [];

    public array $selectedMediaIds = [];

    public bool $multiple = false;

    protected $listeners = [
        'set-media-picker-model' => 'setModel',
        'mediaUploaded' => 'refreshMedia',
    ];

    public function setModel(int $modelId, string $modelClass, bool $multiple = false)
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
            ->get()
            ->all();
    }

    public function toggleMediaSelection(int $mediaId)
    {
        if ($this->multiple) {
            if (in_array($mediaId, $this->selectedMediaIds)) {
                $this->selectedMediaIds = array_diff($this->selectedMediaIds, [$mediaId]);
            } else {
                $this->selectedMediaIds[] = $mediaId;
            }
        } else {
            $this->selectedMediaIds = [$mediaId];
        }
    }

    public function applySelection()
    {
        $selectedMedia = Media::whereIn('id', $this->selectedMediaIds)->get();


        if ($selectedMedia->isNotEmpty()) {
            if (!$this->multiple) {
                $media = $selectedMedia->first();
                $this->dispatch('mediaSelected', [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                ]);

            } else {
                $selectedMediaData = $selectedMedia->map(fn($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                ])->toArray();


                $this->dispatch('mediaSelected', $selectedMediaData);
            }
        } else {
            $this->dispatch('mediaSelected', []);
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
