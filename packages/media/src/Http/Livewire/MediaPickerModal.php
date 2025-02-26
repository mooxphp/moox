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
        'mime_type' => '',
    ];

    public string $searchQuery = '';

    public string $fileTypeFilter = '';

    public string $dateFilter = '';

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
                $query->where(function ($subQuery) {
                    $subQuery->where('file_name', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('title', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('description', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('alt', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->when($this->fileTypeFilter, function ($query) {
                switch ($this->fileTypeFilter) {
                    case 'images':
                        $query->whereIn('mime_type', [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/svg+xml',
                        ]);
                        break;
                    case 'videos':
                        $query->whereIn('mime_type', [
                            'video/mp4',
                            'video/webm',
                        ]);
                        break;
                    case 'audios':
                        $query->whereIn('mime_type', [
                            'audio/mpeg',
                            'audio/ogg',
                        ]);
                        break;
                    case 'documents':
                        $query->whereIn('mime_type', [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ]);
                        break;
                }
            })
            ->when($this->dateFilter, function ($query) {
                switch ($this->dateFilter) {
                    case 'today':
                        $query->whereDate('created_at', now()->toDateString());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [now()->subDays(7), now()]);
                        break;
                    case 'month':
                        $query->whereBetween('created_at', [now()->subMonth(), now()]);
                        break;
                    case 'year':
                        $query->whereBetween('created_at', [now()->subYear(), now()]);
                        break;
                }
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
            if (! empty($this->selectedMediaIds) && $this->selectedMediaIds[0] === $mediaId) {
                $this->selectedMediaIds = [];
            } else {
                $this->selectedMediaIds = [$mediaId];
            }
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
                'mime_type' => $media->mime_type ?? '',
            ];
        } else {
            $this->selectedMediaMeta = [
                'id' => null,
                'file_name' => '',
                'title' => '',
                'description' => '',
                'internal_note' => '',
                'alt' => '',
                'mime_type' => '',
            ];
        }
    }

    public function applySelection()
    {
        $selectedMedia = Media::whereIn('id', $this->selectedMediaIds)->get();

        if ($selectedMedia->isNotEmpty()) {
            if (! $this->multiple) {
                $media = $selectedMedia->first();
                $this->dispatch('mediaSelected', [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                ]);
            } else {
                $selectedMediaData = $selectedMedia->map(fn ($media) => [
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

    public function updatedFileTypeFilter()
    {
        $this->refreshMedia();
    }

    public function updatedDateFilter()
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
