<?php

namespace Moox\Media\Http\Livewire;

use Exception;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

/** @property \Filament\Schemas\Schema $form */
class MediaPickerModal extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;
    use WithPagination;

    public ?int $modelId = null;

    public ?string $modelClass = null;

    public ?Model $model = null;

    public $media;

    public array $selectedMediaIds = [];

    public bool $multiple = false;

    public $files = [];

    public ?array $data = [];

    public array $uploadConfig = [];

    public array $selectedMediaMeta = [
        'id' => null,
        'file_name' => '',
        'name' => '',
        'title' => '',
        'description' => '',
        'internal_note' => '',
        'alt' => '',
        'mime_type' => '',
        'write_protected' => false,
    ];

    public string $searchQuery = '';

    public string $fileTypeFilter = '';

    public string $dateFilter = '';

    public string $uploaderFilter = '';

    public ?string $collection_name = '';

    public ?string $media_collection_id = '';

    public ?string $collectionFilter = '';

    public array $processedHashes = [];

    protected $listeners = [
        'set-media-picker-model' => 'setModel',
        'mediaUploaded' => 'refreshMedia',
    ];

    public function mount(?int $modelId = null, ?string $modelClass = null): void
    {
        $this->files = [];
        $this->form->fill();

        $this->modelClass = $modelClass;
        $this->modelId = $modelId;

        $this->modelClass = $this->modelClass ? str_replace('\\\\', '\\', $this->modelClass) : null;

        if ($this->modelClass && ! class_exists($this->modelClass)) {
            throw new Exception(__('media::fields.class_not_found', ['class' => $this->modelClass]));
        }

        if ($this->modelId && $this->modelClass) {
            $this->model = app($this->modelClass)::find($this->modelId);
        }

        if (! $this->modelId || ! $this->model) {
            $this->modelId = 0;
        }

        $firstCollection = MediaCollection::first();
        if (! $firstCollection) {
            $firstCollection = MediaCollection::create([
                'name' => __('media::fields.uncategorized'),
                'description' => __('media::fields.uncategorized_description'),
            ]);
        }
        $this->collection_name = $firstCollection->id;
    }

    public function form(Schema $schema): Schema
    {
        $collection = Select::make('media_collection_id')
            ->label(__('media::fields.collection'))
            ->options(fn () => MediaCollection::whereHas('translations', function ($query) {
                $query->where('locale', app()->getLocale());
            })->get()->pluck('name', 'id')->filter()->toArray())
            ->searchable()
            ->default(MediaCollection::first()->id)
            ->required()
            ->live();

        $upload = FileUpload::make(__('files'))
            ->label(__('media::fields.upload'))
            ->live()
            ->afterStateUpdated(function ($state, $get) {
                if (! $state) {
                    return;
                }

                $collectionId = $get('media_collection_id');
                $collection = MediaCollection::find($collectionId);
                $collectionName = $collection?->name ?? __('media::fields.uncategorized');

                foreach ($state as $tempFile) {
                    $fileHash = hash_file('sha256', $tempFile->getRealPath());

                    if (in_array($fileHash, $this->processedHashes)) {
                        continue;
                    }

                    $fileName = $tempFile->getClientOriginalName();

                    $existingMedia = Media::whereHas('translations', function ($query) use ($fileName) {
                        $query->where('name', $fileName);
                    })->orWhere(function ($query) use ($fileHash) {
                        $query->where('custom_properties->file_hash', $fileHash);
                    })->first();

                    if ($existingMedia) {
                        Notification::make()
                            ->warning()
                            ->title(__('media::fields.duplicate_file'))
                            ->body(__('media::fields.duplicate_file_message', [
                                'fileName' => $fileName,
                            ]))
                            ->persistent()
                            ->send();

                        continue;
                    }

                    $model = new Media;
                    $model->exists = true;

                    $fileAdder = app(FileAdderFactory::class)->create($model, $tempFile);
                    $media = $fileAdder->preservingOriginal()->toMediaCollection($collectionName);

                    $media->media_collection_id = $collectionId;
                    $media->save();

                    $title = pathinfo($fileName, PATHINFO_FILENAME);

                    $media->title = $title;
                    $media->alt = $title;
                    $media->uploader_type = get_class(auth()->user());
                    $media->uploader_id = auth()->id();
                    $media->original_model_type = Media::class;
                    $media->original_model_id = $media->id;
                    $media->model_id = $media->id;
                    $media->model_type = Media::class;

                    $media->setCustomProperty('file_hash', $fileHash);

                    if (str_starts_with($media->mime_type, 'image/')) {
                        [$width, $height] = getimagesize($media->getPath());
                        $media->setCustomProperty('dimensions', [
                            'width' => $width,
                            'height' => $height,
                        ]);
                    }

                    $media->save();
                    $this->processedHashes[] = $fileHash;
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
        if (isset($this->uploadConfig['show_download_button']) && $this->uploadConfig['show_download_button']) {
            $upload->downloadable();
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

        return $schema->components([$collection, $upload]);
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

        $media = Media::where('id', $mediaId)->first();

        if ($media) {
            $uploaderName = '-';
            if ($media->uploader) {
                if (isset($media->uploader->name)) {
                    $uploaderName = $media->uploader->name;
                } elseif (isset($media->uploader->first_name) && isset($media->uploader->last_name)) {
                    $uploaderName = $media->uploader->first_name.' '.$media->uploader->last_name;
                }
            }

            $this->selectedMediaMeta = [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'name' => $media->getAttribute('name') ?? '',
                'title' => $media->getAttribute('title') ?? '',
                'description' => $media->getAttribute('description') ?? '',
                'internal_note' => $media->getAttribute('internal_note') ?? '',
                'alt' => $media->getAttribute('alt') ?? '',
                'mime_type' => $media->getReadableMimeType(),
                'write_protected' => (bool) $media->getOriginal('write_protected'),
                'size' => $media->size,
                'dimensions' => $media->getCustomProperty('dimensions', []),
                'created_at' => $media->created_at,
                'updated_at' => $media->updated_at,
                'uploader_name' => $uploaderName,
                'collection_name' => $media->collection_name,
                'media_collection_id' => $media->media_collection_id,
            ];
        } else {
            $this->selectedMediaMeta = [
                'id' => null,
                'file_name' => '',
                'name' => '',
                'title' => '',
                'description' => '',
                'internal_note' => '',
                'alt' => '',
                'mime_type' => '',
                'write_protected' => false,
                'size' => 0,
                'dimensions' => [],
                'created_at' => null,
                'updated_at' => null,
                'uploader_name' => '-',
                'collection_name' => '',
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
                    'mime_type' => $media->mime_type,
                    'name' => $media->getAttribute('name'),
                ]);
            } else {
                $selectedMediaData = $selectedMedia->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'name' => $media->getAttribute('name'),
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
            $media = Media::where('id', $this->selectedMediaMeta['id'])->first();

            if (in_array($field, ['title', 'description', 'internal_note', 'alt', 'name', 'media_collection_id'])) {
                if ($media->getOriginal('write_protected')) {
                    return;
                }

                $media->setAttribute($field, $value);

                if ($field === 'media_collection_id') {
                    $collection = MediaCollection::find($value);
                    $media->collection_name = $collection?->name ?? null;
                    $this->selectedMediaMeta['collection_name'] = $media->collection_name;
                }

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
                        ->orWhereHas('translations', function ($query) {
                            $query->where('title', 'like', '%'.$this->searchQuery.'%')
                                ->orWhere('description', 'like', '%'.$this->searchQuery.'%')
                                ->orWhere('alt', 'like', '%'.$this->searchQuery.'%');
                        });
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
            ->when($this->uploaderFilter, function ($query) {
                $parts = explode('::', $this->uploaderFilter);
                if (count($parts) === 2) {
                    $query->where('uploader_type', $parts[0])
                        ->where('uploader_id', $parts[1]);
                }
            })
            ->when($this->collectionFilter, function ($query) {
                $query->where('media_collection_id', $this->collectionFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(18);

        $collectionOptions = MediaCollection::whereHas('translations', function ($query) {
            $query->where('locale', app()->getLocale());
        })->get()->pluck('name', 'id')->filter()->toArray();

        $uploaderOptions = [];
        $uploaderTypes = Media::query()
            ->distinct()
            ->whereNotNull('uploader_type')
            ->pluck('uploader_type')
            ->toArray();

        foreach ($uploaderTypes as $type) {
            $mediaItems = Media::query()
                ->where('uploader_type', $type)
                ->whereNotNull('uploader_id')
                ->with('uploader')
                ->get();

            $uploaders = $mediaItems
                ->map(function (Media $media): ?array {
                    $uploader = $media->uploader;
                    if ($uploader && method_exists($uploader, 'getName')) {
                        return [
                            'id' => $media->uploader_type.'::'.$media->uploader_id,
                            'name' => $uploader->getName(),
                        ];
                    }
                    if ($uploader && isset($uploader->name)) {
                        return [
                            'id' => $media->uploader_type.'::'.$media->uploader_id,
                            'name' => $uploader->name,
                        ];
                    }

                    return null;
                })
                ->filter()
                ->unique(fn (array $item): string => $item['id'])
                ->pluck('name', 'id')
                ->toArray();

            if (! empty($uploaders)) {
                $typeName = class_basename($type);
                $uploaderOptions[$typeName] = $uploaders;
            }
        }

        return view('media::livewire.media-picker-modal', [
            'mediaItems' => $media,
            'uploaderOptions' => $uploaderOptions,
            'collectionOptions' => $collectionOptions,
        ]);
    }
}
