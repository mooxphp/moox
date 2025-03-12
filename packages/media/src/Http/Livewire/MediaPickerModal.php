<?php

namespace Moox\Media\Http\Livewire;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Moox\Media\Models\Media;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class MediaPickerModal extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;
    use WithPagination;

    public ?int $modelId = null;

    public ?string $modelClass = null;

    public $media;

    public array $selectedMediaIds = [];

    public bool $multiple = false;

    public $files = [];

    public ?array $data = [];

    public array $uploadConfig = [];

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

    public function mount(): void
    {
        $this->files = [];
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $upload = FileUpload::make('files')
            ->afterStateUpdated(function ($state) {
                if (! $state) {
                    return;
                }

                $processedFiles = session('processed_files', []);

                if (! is_array($state)) {
                    $model = new Media;
                    $model->exists = true;

                    $fileAdder = app(FileAdderFactory::class)->create($model, $state);
                    $media = $fileAdder->preservingOriginal()->toMediaCollection('default');

                    $title = pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME);

                    $media->title = $title;
                    $media->alt = $title;
                    $media->original_model_type = Media::class;
                    $media->original_model_id = $media->id;
                    $media->model_id = $media->id;
                    $media->model_type = Media::class;

                    if (str_starts_with($media->mime_type, 'image/')) {
                        [$width, $height] = getimagesize($media->getPath());
                        $media->setCustomProperty('dimensions', [
                            'width' => $width,
                            'height' => $height,
                        ]);
                    }

                    $media->save();
                } else {
                    foreach ($state as $key => $tempFile) {
                        if (in_array($key, $processedFiles)) {
                            continue;
                        }

                        $model = new Media;
                        $model->exists = true;

                        $fileAdder = app(FileAdderFactory::class)->create($model, $tempFile);
                        $media = $fileAdder->preservingOriginal()->toMediaCollection('default');

                        $title = pathinfo($tempFile->getClientOriginalName(), PATHINFO_FILENAME);

                        $media->title = $title;
                        $media->alt = $title;
                        $media->original_model_type = Media::class;
                        $media->original_model_id = $media->id;
                        $media->model_id = $media->id;
                        $media->model_type = Media::class;

                        if (str_starts_with($media->mime_type, 'image/')) {
                            [$width, $height] = getimagesize($media->getPath());
                            $media->setCustomProperty('dimensions', [
                                'width' => $width,
                                'height' => $height,
                            ]);
                        }

                        $media->save();
                        $processedFiles[] = $key;
                    }

                    session(['processed_files' => $processedFiles]);
                }
            });

        if (isset($this->uploadConfig['multiple'])) {
            $upload->multiple($this->uploadConfig['multiple']);
        }
        if (isset($this->uploadConfig['accepted_file_types'])) {
            $upload->acceptedFileTypes($this->uploadConfig['accepted_file_types']);
        }
        if (isset($this->uploadConfig['max_files'])) {
            $upload->maxFiles($this->uploadConfig['max_files']);
        }
        if (isset($this->uploadConfig['min_files'])) {
            $upload->minFiles($this->uploadConfig['min_files']);
        }
        if (isset($this->uploadConfig['max_size'])) {
            $upload->maxSize($this->uploadConfig['max_size']);
        }
        if (isset($this->uploadConfig['min_size'])) {
            $upload->minSize($this->uploadConfig['min_size']);
        }
        if (isset($this->uploadConfig['image_editor'])) {
            $upload->imageEditor($this->uploadConfig['image_editor']);
        }
        if (isset($this->uploadConfig['image_editor_mode'])) {
            $upload->imageEditorMode($this->uploadConfig['image_editor_mode']);
        }
        if (isset($this->uploadConfig['image_editor_viewport_width'])) {
            $upload->imageEditorViewportWidth($this->uploadConfig['image_editor_viewport_width']);
        }
        if (isset($this->uploadConfig['image_editor_viewport_height'])) {
            $upload->imageEditorViewportHeight($this->uploadConfig['image_editor_viewport_height']);
        }
        if (isset($this->uploadConfig['image_editor_aspect_ratios'])) {
            $upload->imageEditorAspectRatios($this->uploadConfig['image_editor_aspect_ratios']);
        }
        if (isset($this->uploadConfig['placeholder'])) {
            $upload->placeholder($this->uploadConfig['placeholder']);
        }
        if (isset($this->uploadConfig['panel_layout'])) {
            $upload->panelLayout($this->uploadConfig['panel_layout']);
        }
        if (isset($this->uploadConfig['show_download_button'])) {
            $upload->showDownloadButton($this->uploadConfig['show_download_button']);
        }
        if (isset($this->uploadConfig['disk'])) {
            $upload->disk($this->uploadConfig['disk']);
        }
        if (isset($this->uploadConfig['directory'])) {
            $upload->directory($this->uploadConfig['directory']);
        }
        if (isset($this->uploadConfig['visibility'])) {
            $upload->visibility($this->uploadConfig['visibility']);
        }

        return $form->schema([$upload]);
    }

    public function setModel(?int $modelId, string $modelClass): void
    {
        $this->modelId = $modelId;
        $this->modelClass = $modelClass;
        $this->refreshMedia();
    }

    public function refreshMedia()
    {
        $this->render();
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

    public function updatingSearchQuery()
    {
        $this->resetPage();
    }

    public function updatingFileTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $media = Media::query()
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
            ->paginate(18);

        return view('media::livewire.media-picker-modal', [
            'mediaItems' => $media,
        ]);
    }
}
