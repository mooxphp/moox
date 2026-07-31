<?php

namespace Moox\Media\Resources\MediaResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaResource;
use Moox\Media\Support\MediaLocaleResolver;
use Moox\Media\Support\MediaUploadValidator;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class ListMedia extends BaseListDrafts
{
    protected static string $resource = MediaResource::class;

    protected array $processedFiles = [];

    public array $processedHashes = [];

    public bool $isSelecting = false;

    public array $selected = [];

    public bool $isGridView = true;

    public string $lang;

    public function mount(): void
    {
        parent::mount();
        $this->isGridView = session('media_grid_view', true);
        $this->lang = request()->query('lang', app(MediaLocaleResolver::class)->adminDefaultLocale());
    }

    public function saveTranslationFromForm($recordId)
    {
        $record = static::getResource()::scopeQuery(Media::query())->find($recordId);

        if ($record && method_exists($record, 'translateOrNew')) {
            $lang = $this->lang ?? app()->getLocale();
            $translation = $record->translateOrNew($lang);

            $formData = [];
            if (! empty($this->mountedActions)) {
                foreach ($this->mountedActions as $action) {
                    if (isset($action['data'])) {
                        $formData = $action['data'];
                        break;
                    }
                }
            }

            $translationMapping = [
                'name' => 'name',
                'title' => 'title',
                'alt' => 'alt',
                'description' => 'description',
                'internal_note' => 'internal_note',
            ];

            if (empty($formData['name'])) {
                Notification::make()
                    ->title(__('media::fields.validation_error'))
                    ->body(__('media::fields.name_required'))
                    ->danger()
                    ->send();

                return;
            }

            foreach ($translationMapping as $formField => $dbField) {
                if (isset($formData[$formField])) {
                    $translation->$dbField = $formData[$formField];
                }
            }

            $translation->save();

            Notification::make()
                ->title(__('media::fields.translation_saved'))
                ->body(__('media::fields.translation_saved_message', ['lang' => $lang]))
                ->success()
                ->send();

            $this->dispatch('$refresh');
        }
    }

    public function toggleView(): void
    {
        $this->isGridView = ! $this->isGridView;
        session(['media_grid_view' => $this->isGridView]);

        $this->resetTable();
    }

    public function openUsageModal(int $mediaId): void
    {
        $this->dispatch('open-modal', id: "usage-modal-{$mediaId}");
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('toggleView')
                ->label(fn () => $this->isGridView ? __('media::fields.table_view') : __('media::fields.grid_view'))
                ->icon(fn () => $this->isGridView ? 'heroicon-m-table-cells' : 'heroicon-m-squares-2x2')
                ->action(fn () => $this->toggleView())
                ->color('gray'),
            Action::make('upload')
                ->label(__('media::fields.upload_file'))
                ->icon(config('media.upload.resource.icon'))
                ->schema([
                    Select::make('media_collection_id')
                        ->label(__('media::fields.collection'))
                        ->options(function () {
                            $localeResolver = app(MediaLocaleResolver::class);
                            $currentLang = $this->lang ?? app()->getLocale();

                            return MediaCollection::query()
                                ->with('translations')
                                ->get()
                                ->mapWithKeys(fn (MediaCollection $collection): array => [
                                    $collection->id => $localeResolver->collectionName($collection, $currentLang),
                                ])
                                ->filter(fn (string $name): bool => filled($name))
                                ->all();
                        })
                        ->default(MediaCollection::query()->first()?->id)
                        ->searchable()
                        ->required()
                        ->live(),
                    FileUpload::make('file')
                        ->label(__('media::fields.select_file'))
                        ->multiple(config('media.upload.resource.multiple'))
                        ->disk(config('media.upload.resource.disk'))
                        ->directory(config('media.upload.resource.directory'))
                        ->visibility(config('media.upload.resource.visibility'))
                        ->maxSize(config('media.upload.resource.max_file_size'))
                        ->minSize(config('media.upload.resource.min_file_size'))
                        ->maxFiles(config('media.upload.resource.max_files'))
                        ->minFiles(config('media.upload.resource.min_files'))
                        ->acceptedFileTypes(config('media.upload.resource.accepted_file_types'))
                        ->imageResizeMode(config('media.upload.resource.image_resize_mode'))
                        ->imageCropAspectRatio(config('media.upload.resource.image_crop_aspect_ratio'))
                        ->imageResizeTargetWidth(config('media.upload.resource.image_resize_target_width'))
                        ->imageResizeTargetHeight(config('media.upload.resource.image_resize_target_height'))
                        ->imageEditor(config('media.upload.resource.image_editor.enabled'))
                        ->imageEditorAspectRatios(config('media.upload.resource.image_editor.aspect_ratios'))
                        ->imageEditorViewportWidth(config('media.upload.resource.image_editor.viewport_width'))
                        ->imageEditorViewportHeight(config('media.upload.resource.image_editor.viewport_height'))
                        ->imageEditorMode(config('media.upload.resource.image_editor.mode'))
                        ->imageEditorEmptyFillColor(config('media.upload.resource.image_editor.empty_fill_color'))
                        ->panelLayout(config('media.upload.resource.panel_layout'))
                        ->downloadable(config('media.upload.resource.show_download_button'))
                        ->openable(config('media.upload.resource.show_open_button'))
                        ->previewable(config('media.upload.resource.show_preview'))
                        ->reorderable(config('media.upload.resource.reorderable'))
                        ->appendFiles(config('media.upload.resource.append_files'))
                        ->afterStateUpdated(function ($state, $get) {
                            if (! $state) {
                                return;
                            }

                            $collectionId = $get('media_collection_id');
                            $collection = MediaCollection::with('translations')->find($collectionId);
                            $localeResolver = app(MediaLocaleResolver::class);

                            $collectionName = __('media::fields.uncategorized');
                            if ($collection) {
                                $collectionName = $localeResolver->collectionName($collection, $this->lang ?? app()->getLocale());
                            }

                            $uploadLang = $this->lang ?: $localeResolver->adminDefaultLocale();

                            foreach ($state as $tempFile) {
                                app(MediaUploadValidator::class)->ensureAccepted($tempFile);

                                $fileHash = hash_file('sha256', $tempFile->getRealPath());

                                if (in_array($fileHash, $this->processedHashes)) {
                                    continue;
                                }

                                $fileName = $tempFile->getClientOriginalName();

                                $existingMedia = static::getResource()::scopeQuery(Media::query())
                                    ->where(function ($query) use ($fileName, $fileHash) {
                                        $query->whereHas('translations', function ($query) use ($fileName) {
                                            $query->where('name', $fileName);
                                        })->orWhere(function ($query) use ($fileHash) {
                                            $query->where('custom_properties->file_hash', $fileHash);
                                        });
                                    })
                                    ->first();

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

                                $localeResolver->withLocale($uploadLang, function () use ($collectionId, $collectionName, $fileHash, $fileName, $tempFile, $uploadLang): void {
                                    $model = new Media;
                                    $model->exists = true;

                                    $fileAdder = FileAdderFactory::create($model, $tempFile);
                                    $tempFileSize = method_exists($tempFile, 'getSize') ? $tempFile->getSize() : null;
                                    if (is_int($tempFileSize) && $tempFileSize > 0) {
                                        $fileAdder->setFileSize($tempFileSize);
                                    }
                                    /** @var Media $media */
                                    $media = $fileAdder->preservingOriginal()->toMediaCollection($collectionName);

                                    $media->media_collection_id = $collectionId;
                                    $media->collection_name = $collectionName;
                                    static::getResource()::applyScopedDefaults($media);
                                    $media->save();

                                    $title = pathinfo($tempFile->getClientOriginalName(), PATHINFO_FILENAME);

                                    $translation = $media->translateOrNew($uploadLang);
                                    $translation->setAttribute('name', $fileName);
                                    $translation->setAttribute('title', $title);
                                    $translation->setAttribute('alt', $title);
                                    $translation->save();
                                    $user = auth()->user();
                                    $media->uploader_type = $user ? get_class($user) : null;
                                    $media->uploader_id = auth()->id();
                                    $media->original_model_type = Media::class;
                                    $media->original_model_id = $media->getKey();
                                    $media->model_id = $media->getKey();
                                    $media->model_type = Media::class;

                                    $media->setCustomProperty('file_hash', $fileHash);

                                    if (str_starts_with((string) $media->mime_type, 'image/')) {
                                        $size = @getimagesize($media->getPath());
                                        if ($size !== false) {
                                            $media->setCustomProperty('dimensions', [
                                                'width' => (int) $size[0],
                                                'height' => (int) $size[1],
                                            ]);
                                        }
                                    }

                                    $media->save();
                                });

                                $this->processedHashes[] = $fileHash;
                            }
                        }),
                ])
                ->modalSubmitAction(false),
        ];
    }
}
