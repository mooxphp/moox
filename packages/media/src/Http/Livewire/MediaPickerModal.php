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

    public array $selectedMediaMeta = [
        'id' => null,
        'file_name' => '',
        'title' => '',
        'description' => '',
        'internal_note' => '',
        'alt' => '',
    ];

    public string $searchQuery = '';

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
            ->when($this->searchQuery, function ($query) {
                $query->where('file_name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('title', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('description', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('alt', 'like', '%' . $this->searchQuery . '%');
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

        $media = Media::find($mediaId);

        if ($media) {
            $this->selectedMediaMeta = [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'title' => $media->title ?? '',
                'description' => $media->description ?? '',
                'internal_note' => $media->internal_note ?? '',
                'alt' => $media->alt ?? '',
            ];
        } else {
            $this->selectedMediaMeta = [
                'id' => null,
                'file_name' => '',
                'title' => '',
                'description' => '',
                'internal_note' => '',
                'alt' => '',
            ];
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

    public function updatedSelectedMediaMeta($value, $field)
    {
        if ($this->selectedMediaMeta['id']) {
            $media = Media::find($this->selectedMediaMeta['id']);

            if (in_array($field, ['title', 'description', 'internal_note', 'alt'])) {
                $media->$field = $value;
                $media->save();
            }
        }
    }

    public function updatedSearchQuery()
    {
        $this->refreshMedia();
    }



    public function render()
    {
        return view('media::livewire.media-picker-modal', [
            'media' => $this->media,
        ]);
    }
}
