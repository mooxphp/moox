<?php

namespace Moox\Media\Resources;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Moox\Media\Models\Media;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\View;
use Moox\Media\Models\MediaCollection;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Collection;
use Moox\Media\Resources\MediaResource\Pages;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Filament\Tables\Actions\HeaderActionsPosition;
use Moox\Media\Resources\MediaResource\Pages\ListMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class MediaResource extends Resource
{
    use BaseInResource;

    protected static ?string $model = Media::class;

    protected static \BackedEnum|string|null $navigationIcon = 'gmdi-view-timeline-o';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return config('media.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return config('media.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('media.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        $saveRecord = function ($state, $old, $component) use ($schema) {
            $record = $schema->getRecord();
            if ($state !== $old) {
                $record->{$component->getName()} = $state;
                $record->save();
            }
        };

        return $schema->schema([
            SpatieMediaLibraryFileUpload::make('file_name')
                ->columnSpanFull()
                ->collection(function ($record) {
                    $mediaCollection = $record->collection_name;

                    if ($record->getFirstMedia()) {
                        return $record->getFirstMedia()->collection_name;
                    }

                    return $mediaCollection;
                })
                ->imageEditor(fn($record) => !$record->write_protected)
                ->downloadable(fn($record) => !$record->write_protected)
                ->deletable(fn($record) => !$record->write_protected)
                ->openable()
                ->afterStateUpdated(function ($state, $record) {
                    if ($state && $record) {
                        try {
                            $fileHash = hash_file('sha256', $state->getRealPath());
                            $fileName = $state->getClientOriginalName();

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

                                return;
                            }

                            $oldMedia = $record->getFirstMedia();
                            $oldMediaId = $oldMedia?->id;

                            $originalFileName = pathinfo($oldMedia?->file_name, PATHINFO_FILENAME);
                            $newFileName = pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME);

                            $isEdit = preg_match('/-v\d+$/', $newFileName);

                            $usables = [];
                            if ($oldMediaId && !$isEdit) {
                                $usables = \DB::table('media_usables')
                                    ->where('media_id', $oldMediaId)
                                    ->get()
                                    ->map(function ($item) {
                                        return (array) $item;
                                    })
                                    ->toArray();
                            }

                            if ($oldMediaId && !$isEdit) {
                                \DB::table('media')->where('id', $oldMediaId)->delete();
                            }

                            $model = new Media;
                            $model->exists = true;

                            $fileAdder = app(FileAdderFactory::class)->create($model, $state);
                            $collection = $oldMedia ? $oldMedia->collection_name : $record->collection_name;
                            $media = $fileAdder->preservingOriginal()->toMediaCollection($collection);

                            $title = $newFileName;
                            $media->setAttribute('title', $title);
                            $media->setAttribute('alt', $title);
                            $media->uploader_type = get_class(auth()->user());
                            $media->uploader_id = auth()->id();

                            $media->setCustomProperty('file_hash', $fileHash);

                            if ($isEdit && $oldMedia) {
                                $media->original_model_type = $oldMedia->original_model_type;
                                $media->original_model_id = $oldMedia->original_model_id;
                            } else {
                                $media->original_model_type = Media::class;
                                $media->original_model_id = $media->id;
                            }

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

                            if (!$isEdit) {
                                foreach ($usables as $usable) {
                                    \DB::table('media_usables')->insert([
                                        'media_id' => $media->id,
                                        'media_usable_id' => $usable['media_usable_id'],
                                        'media_usable_type' => $usable['media_usable_type'],
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);

                                    // Update the model with the new media data
                                    $model = $usable['media_usable_type']::find($usable['media_usable_id']);
                                    if ($model) {
                                        foreach ($model->getAttributes() as $field => $value) {
                                            $jsonData = json_decode($value, true);

                                            if (!is_array($jsonData)) {
                                                continue;
                                            }

                                            if (isset($jsonData['file_name']) && $jsonData['file_name'] === $oldMedia->file_name) {
                                                $model->{$field} = json_encode([
                                                    'file_name' => $media->file_name,
                                                    'title' => $media->title,
                                                    'description' => $media->description,
                                                    'internal_note' => $media->internal_note,
                                                    'alt' => $media->alt,
                                                ]);

                                                continue;
                                            }

                                            $changed = false;
                                            foreach ($jsonData as $key => $item) {
                                                if (is_array($item) && isset($item['file_name']) && $item['file_name'] === $oldMedia->file_name) {
                                                    $jsonData[$key] = [
                                                        'file_name' => $media->file_name,
                                                        'title' => $media->title,
                                                        'description' => $media->description,
                                                        'internal_note' => $media->internal_note,
                                                        'alt' => $media->alt,
                                                    ];
                                                    $changed = true;
                                                }
                                            }

                                            if ($changed) {
                                                $model->{$field} = json_encode($jsonData);
                                            }
                                        }

                                        $model->save();
                                    }
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title($isEdit
                                    ? __('media::fields.edit_file_success', ['fileName' => $state->getClientOriginalName()])
                                    : __('media::fields.replace_file_success', [
                                        'oldFileName' => $oldMedia->file_name ?? 'unknown',
                                        'newFileName' => $state->getClientOriginalName(),
                                    ]))
                                ->send();

                            return redirect()->to(ListMedia::getUrl());
                        } catch (\Exception $e) {
                            Log::error('File operation failed', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);

                            Notification::make()
                                ->danger()
                                ->title(__('media::fields.operation_error'))
                                ->body(__('media::fields.file_operation_error', ['fileName' => $record->file_name]))
                                ->persistent()
                                ->send();
                        }
                    }
                }),

            Section::make()
                ->schema([
                    Placeholder::make('file_name')
                        ->label(__('media::fields.file_name'))
                        ->content(fn($record) => $record->file_name),

                    Placeholder::make('mime_type')
                        ->label(__('media::fields.mime_type'))
                        ->content(fn($record) => $record->getReadableMimeType()),

                    Placeholder::make('size')
                        ->label(__('media::fields.size'))
                        ->content(function ($record) {
                            $bytes = $record->size;
                            $units = ['B', 'KB', 'MB', 'GB'];
                            $i = 0;

                            while ($bytes >= 1024 && $i < count($units) - 1) {
                                $bytes /= 1024;
                                $i++;
                            }

                            return number_format($bytes, 2) . ' ' . $units[$i];
                        }),

                    Placeholder::make('dimensions')
                        ->label(__('media::fields.dimensions'))
                        ->content(function ($record) {
                            $dimensions = $record->getCustomProperty('dimensions');
                            if (!$dimensions) {
                                return '-';
                            }

                            return "{$dimensions['width']} × {$dimensions['height']} Pixel";
                        })
                        ->visible(fn($record) => str_starts_with($record->mime_type, 'image/')),

                    Placeholder::make('created_at')
                        ->label(__('media::fields.created_at'))
                        ->content(fn($record) => $record->created_at?->format('d.m.Y H:i')),

                    Placeholder::make('updated_at')
                        ->label(__('media::fields.updated_at'))
                        ->content(fn($record) => $record->updated_at?->format('d.m.Y H:i')),

                    Placeholder::make('uploaded_by')
                        ->label(__('media::fields.uploaded_by'))
                        ->content(function ($record) {
                            if (!$record->uploader) {
                                return '-';
                            }

                            return $record->uploader->name;
                        }),

                    Placeholder::make('usage')
                        ->label(__('media::fields.usage'))
                        ->content(function ($record) {
                            $usages = \DB::table('media_usables')
                                ->where('media_id', $record->id)
                                ->get();

                            if ($usages->isEmpty()) {
                                return __('media::fields.not_used');
                            }

                            return new \Illuminate\Support\HtmlString("
                                <button
                                    type=\"button\"
                                    x-on:click=\"\$dispatch('open-modal', { id: 'usage-modal-{$record->id}' })\"
                                    class=\"filament-button filament-button-size-sm inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2rem] px-3 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10 border-primary-200 dark:border-primary-500/20 hover:bg-primary-100 dark:hover:bg-primary-500/20 focus:ring-primary-600 dark:focus:ring-primary-400 focus:text-primary-600 dark:focus:text-primary-400 focus:bg-primary-50 dark:focus:bg-primary-500/10 focus:border-primary-600 dark:focus:border-primary-400\"
                                >
                                    <span class=\"flex items-center gap-1\">
                                        <svg class=\"w-4 h-4\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\">
                                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244\" />
                                        </svg>
                                        <span>{$usages->count()} " . trans_choice('media::fields.link|links', $usages->count()) . '</span>
                                    </span>
                                </button>
                            ');
                        }),

                    Select::make('collection_name')
                        ->label(__('media::fields.collection'))
                        ->disabled(fn($record) => $record?->getOriginal('write_protected'))
                        ->options(function () {
                            return MediaCollection::query()
                                ->pluck('name', 'name')
                                ->toArray();
                        })
                        ->default(fn($record) => $record->collection_name)
                        ->afterStateUpdated(function ($state, $record) {
                            if ($state !== $record->collection_name) {
                                $record->collection_name = $state;
                                $record->save();
                            }
                        }),
                ])
                ->columns(4),

            Section::make(__('media::fields.metadata'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('media::fields.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord)
                        ->disabled(fn($record) => $record?->getOriginal('write_protected')),

                    TextInput::make('title')
                        ->label(__('media::fields.title'))
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord)
                        ->disabled(fn($record) => $record?->getOriginal('write_protected')),

                    TextInput::make('alt')
                        ->label(__('media::fields.alt_text'))
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord)
                        ->disabled(fn($record) => $record?->getOriginal('write_protected')),

                    Textarea::make('description')
                        ->label(__('media::fields.description'))
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord)
                        ->disabled(fn($record) => $record?->getOriginal('write_protected')),
                ])
                ->columns(2)
                ->collapsed(),

            Section::make(__('media::fields.internal_note'))
                ->schema([
                    TextInput::make('internal_note')
                        ->live(onBlur: true)
                        ->afterStateUpdated($saveRecord)
                        ->disabled(fn($record) => $record?->getOriginal('write_protected')),
                ])
                ->collapsed(),

            View::make('media::components.usage-modal'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $columns = [];

        if ($table->getLivewire()->isGridView) {
            $columns[] = Stack::make([
                CustomImageColumn::make('file')
                    ->alignment('center')
                    ->extraImgAttributes(function ($record, $livewire) {
                        $baseStyle = str_starts_with($record->mime_type, 'image/')
                            ? 'width: 100%; height: auto; min-width: 150px; max-width: 250px; aspect-ratio: 1/1; object-fit: cover;'
                            : 'width: 60px; height: auto; margin-top: 20px;';

                        if ($livewire->isSelecting) {
                            $style = $baseStyle . 'opacity: 0.5;';
                            if (in_array($record->id, $livewire->selected)) {
                                $style = $baseStyle . 'outline: 4px solid rgb(59 130 246); opacity: 1;';
                            }
                            return [
                                'class' => 'rounded-lg cursor-pointer',
                                'style' => $style,
                                'wire:click.stop' => "\$set('selected', " .
                                    (in_array($record->id, $livewire->selected)
                                        ? json_encode(array_values(array_diff($livewire->selected, [$record->id])))
                                        : json_encode(array_merge($livewire->selected, [$record->id]))
                                    ) . ')',
                            ];
                        }
                        return [
                            'class' => 'rounded-lg cursor-pointer',
                            'style' => $baseStyle,
                            'x-on:click' => '$wire.call("mountAction", "edit", { record: ' . $record->id . ' })',
                        ];
                    })
                    ->tooltip(fn($record) => $record->title ?? __('media::fields.no_title'))
                    ->searchable(true, function (Builder $query, string $search) {
                        $query->whereHas('translations', function (Builder $query) use ($search) {
                            $query->where('locale', app()->getLocale())
                                ->where(function (Builder $query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('title', 'like', "%{$search}%")
                                        ->orWhere('description', 'like', "%{$search}%")
                                        ->orWhere('alt', 'like', "%{$search}%")
                                        ->orWhere('internal_note', 'like', "%{$search}%");
                                });
                        });
                    }),
                TextColumn::make('file_name')
                    ->label('')
                    ->alignment('center')
                    ->wrap()
                    ->limit(50)
                    ->searchable()
                    ->visible(fn($record) => $record && !str_starts_with($record->mime_type ?? '', 'image/'))
                    ->extraAttributes(function ($record, $livewire) {
                        $baseStyle = 'margin-top: 10px; word-break: break-all;';
                        if ($livewire->isSelecting) {
                            return [
                                'style' => $baseStyle,
                                'class' => 'cursor-pointer',
                                'wire:click.stop' => "\$set('selected', " .
                                    (in_array($record->id, $livewire->selected)
                                        ? json_encode(array_values(array_diff($livewire->selected, [$record->id])))
                                        : json_encode(array_merge($livewire->selected, [$record->id]))
                                    ) . ')',
                            ];
                        }
                        return [
                            'style' => $baseStyle,
                            'class' => 'cursor-pointer',
                            'x-on:click' => '$wire.call("mountAction", "edit", { record: ' . $record->id . ' })',
                        ];
                    }),
            ]);
        } else {
            $columns[] = CustomImageColumn::make('file')
                ->alignment('center')
                ->extraImgAttributes(function ($record) {
                    $baseStyle = str_starts_with($record->mime_type, 'image/')
                        ? 'width: 80px; height: 80px; object-fit: cover;'
                        : 'width: 50px; height: 50px; margin-top: 10px;';

                    return [
                        'class' => 'rounded-lg cursor-pointer',
                        'style' => $baseStyle,
                        'x-on:click' => '$wire.call("mountAction", "edit", { record: ' . $record->id . ' })',
                    ];
                })
                ->tooltip(fn($record) => $record->title ?? __('media::fields.no_title'))
                ->searchable(true, function (Builder $query, string $search) {
                    $query->whereHas('translations', function (Builder $query) use ($search) {
                        $query->where('locale', app()->getLocale())
                            ->where(function (Builder $query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('title', 'like', "%{$search}%")
                                    ->orWhere('description', 'like', "%{$search}%")
                                    ->orWhere('alt', 'like', "%{$search}%")
                                    ->orWhere('internal_note', 'like', "%{$search}%");
                            });
                    });
                });

            $columns[] = TextColumn::make('file_name')
                ->label(__('media::fields.file_name'))
                ->searchable();

            $columns[] = TextColumn::make('name')
                ->label(__('media::fields.name'))
                ->searchable();

            $columns[] = TextColumn::make('collection_name')
                ->label(__('media::fields.collection'))
                ->searchable();

            $columns[] = TextColumn::make('mime_type')
                ->label(__('media::fields.mime_type'))
                ->searchable()
                ->formatStateUsing(fn($record) => $record->getReadableMimeType());

            $columns[] = TextColumn::make('uploader_type')
                ->label(__('media::fields.uploaded_by'))
                ->formatStateUsing(fn($record) => $record->uploader?->name);

            $columns[] = TextColumn::make('created_at')
                ->label(__('media::fields.created_at'))
                ->date();

            $columns[] = TextColumn::make('usages')
                ->label(__('media::fields.usage'))
                ->getStateUsing(function ($record) {
                    $count = \DB::table('media_usables')
                        ->where('media_id', $record->id)
                        ->count();

                    return $count === 0
                        ? __('media::fields.not_used')
                        : $count . ' ' . trans_choice('media::fields.link|links', $count);
                });
        }

        return $table
            ->columns($columns)
            ->contentGrid(fn($livewire) => $livewire->isGridView ? [
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
                '2xl' => 5,
            ] : null)
            ->headerActionsPosition(HeaderActionsPosition::Bottom)
            ->headerActions([
                Action::make('toggleSelect')
                    ->label(function ($livewire) {
                        return $livewire->isSelecting
                            ? __('media::fields.end_selection')
                            : __('media::fields.select_multiple');
                    })
                    ->icon(fn($livewire) => $livewire->isSelecting ? 'heroicon-m-x-mark' : 'heroicon-m-square-2-stack')
                    ->color(fn($livewire) => $livewire->isSelecting ? 'gray' : 'primary')
                    ->visible(fn($livewire) => $livewire->isGridView)
                    ->action(function ($livewire) {
                        $livewire->isSelecting = !$livewire->isSelecting;
                        $livewire->selected = [];
                    }),

                Action::make('deleteSelected')
                    ->label(function ($livewire) {
                        $count = count($livewire->selected);

                        return $count > 0
                            ? __('media::fields.delete_selected') . " ({$count} " . trans_choice('media::fields.file|files', $count) . ')'
                            : __('media::fields.delete_selected');
                    })
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(function ($livewire) {
                        $count = count($livewire->selected);

                        return __('media::fields.delete_selected') . " ({$count} " . trans_choice('media::fields.file|files', $count) . ')';
                    })
                    ->modalDescription(__('media::fields.delete_confirmation'))
                    ->modalSubmitActionLabel(__('media::fields.yes_delete'))
                    ->modalCancelActionLabel(__('media::fields.cancel'))
                    ->visible(fn($livewire) => $livewire->isGridView && $livewire->isSelecting && !empty($livewire->selected))
                    ->action(function ($livewire) {
                        $successCount = 0;
                        $errorCount = 0;
                        $protectedCount = 0;

                        foreach ($livewire->selected as $id) {
                            try {
                                $media = Media::find($id);
                                if (!$media) {
                                    continue;
                                }

                                if (!auth()->user()->can('delete', $media)) {
                                    $protectedCount++;
                                    continue;
                                }

                                if ($media->getOriginal('write_protected')) {
                                    $protectedCount++;
                                    continue;
                                }

                                $media->deletePreservingMedia();
                                $media->delete();
                                $successCount++;
                            } catch (\Exception $e) {
                                Log::error('Media deletion failed: ' . $e->getMessage(), [
                                    'media_id' => $id,
                                ]);
                                $errorCount++;
                            }
                        }

                        if ($successCount > 0) {
                            Notification::make()
                                ->success()
                                ->title($successCount . ' ' . trans_choice('media::fields.file_deleted|files_deleted', $successCount))
                                ->send();
                        }

                        if ($protectedCount > 0) {
                            Notification::make()
                                ->warning()
                                ->title(__('media::fields.protected_skipped'))
                                ->body($protectedCount . ' ' . trans_choice('media::fields.protected_file_skipped|protected_files_skipped', $protectedCount))
                                ->persistent()
                                ->send();
                        }

                        if ($errorCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('media::fields.delete_error'))
                                ->body($errorCount . ' ' . trans_choice('media::fields.file_could_not_be_deleted|files_could_not_be_deleted', $errorCount))
                                ->persistent()
                                ->send();
                        }

                        $livewire->isSelecting = false;
                        $livewire->selected = [];
                    }),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label(__('media::fields.delete_selected'))
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(function (Collection $records) {
                        return __('media::fields.delete_selected') . ' (' . $records->count() . ' ' . trans_choice('media::fields.file|files', $records->count()) . ')';
                    })
                    ->modalDescription(__('media::fields.delete_confirmation'))
                    ->modalSubmitActionLabel(__('media::fields.yes_delete'))
                    ->modalCancelActionLabel(__('media::fields.cancel'))
                    ->visible(fn($livewire) => !$livewire->isGridView)
                    ->action(function (Collection $records) {
                        $successCount = 0;
                        $errorCount = 0;
                        $protectedCount = 0;

                        foreach ($records as $media) {
                            try {
                                if (!auth()->user()->can('delete', $media)) {
                                    $protectedCount++;
                                    continue;
                                }

                                if ($media->getOriginal('write_protected')) {
                                    $protectedCount++;
                                    continue;
                                }

                                $media->deletePreservingMedia();
                                $media->delete();
                                $successCount++;
                            } catch (\Exception $e) {
                                Log::error('Media deletion failed: ' . $e->getMessage(), [
                                    'media_id' => $media->id,
                                ]);
                                $errorCount++;
                            }
                        }

                        if ($successCount > 0) {
                            Notification::make()
                                ->success()
                                ->title($successCount . ' ' . trans_choice('media::fields.file_deleted|files_deleted', $successCount))
                                ->send();
                        }

                        if ($protectedCount > 0) {
                            Notification::make()
                                ->warning()
                                ->title(__('media::fields.protected_skipped'))
                                ->body($protectedCount . ' ' . trans_choice('media::fields.protected_file_skipped|protected_files_skipped', $protectedCount))
                                ->persistent()
                                ->send();
                        }

                        if ($errorCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('media::fields.delete_error'))
                                ->body($errorCount . ' ' . trans_choice('media::fields.file_could_not_be_deleted|files_could_not_be_deleted', $errorCount))
                                ->persistent()
                                ->send();
                        }
                    })
            ])
            ->checkIfRecordIsSelectableUsing(fn($record) => !$record->getOriginal('write_protected'))
            ->actions([
                EditAction::make()
                    ->icon('')
                    ->label('')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('media::fields.cancel'))
                    ->authorize('view')
                    ->extraModalFooterActions([
                        DeleteAction::make()
                            ->label(__('media::fields.delete_file'))
                            ->color('danger')
                            ->icon('heroicon-m-trash')
                            ->requiresConfirmation()
                            ->modalIcon('heroicon-m-trash')
                            ->modalHeading(function ($record) {
                                $usages = \DB::table('media_usables')
                                    ->where('media_id', $record->id)
                                    ->count();

                                if ($usages > 0) {
                                    return __('media::fields.delete_linked_file_heading', [
                                        'title' => $record->title ?: $record->name,
                                        'count' => $usages,
                                        'links' => trans_choice('media::fields.link|links', $usages),
                                    ]);
                                }

                                return __('media::fields.delete_file_heading', ['title' => $record->title ?: $record->name]);
                            })
                            ->modalDescription(function ($record) {
                                $usages = \DB::table('media_usables')
                                    ->where('media_id', $record->id)
                                    ->count();

                                $description = [];

                                if ($usages > 0) {
                                    $description[] = '<div class="text-danger-600 font-medium text-lg mb-2">' .
                                        __('media::fields.warning_file_has_links', [
                                            'count' => $usages,
                                            'links' => trans_choice('media::fields.link|links', $usages),
                                        ]) .
                                        '</div>';

                                    $description[] = '<div class="bg-danger-50 text-danger-700 p-4 rounded-lg mb-4">' .
                                        __('media::fields.delete_linked_warning') .
                                        '</div>';
                                }

                                $description[] = __('media::fields.delete_file_description');

                                return new \Illuminate\Support\HtmlString(implode("\n", $description));
                            })
                            ->modalSubmitActionLabel(__('media::fields.yes_delete'))
                            ->modalCancelActionLabel(__('media::fields.cancel'))
                            ->hidden(fn(Media $record) => !auth()->user()->can('delete', $record) || $record->getOriginal('write_protected'))
                            ->before(function ($record) {
                                try {
                                    if ($record->getOriginal('write_protected')) {
                                        Notification::make()
                                            ->danger()
                                            ->title(__('media::fields.delete_error'))
                                            ->body(__('media::fields.protected_file_error'))
                                            ->persistent()
                                            ->send();

                                        return false;
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('media::fields.delete_error'))
                                        ->body($e->getMessage())
                                        ->persistent()
                                        ->send();

                                    return false;
                                }
                            })
                            ->action(function ($record) {
                                try {
                                    $fileName = $record->file_name;
                                    $record->deletePreservingMedia();
                                    $record->delete();

                                    Notification::make()
                                        ->success()
                                        ->title(__('media::fields.delete_file_success', ['fileName' => $fileName]))
                                        ->send();

                                    return redirect(static::getUrl('index'));
                                } catch (\Exception $e) {
                                    Log::error('Media deletion failed: ' . $e->getMessage(), [
                                        'media_id' => $record->id,
                                        'file_name' => $record->file_name,
                                    ]);

                                    Notification::make()
                                        ->danger()
                                        ->title(__('media::fields.delete_error'))
                                        ->body(__('media::fields.delete_file_error', ['fileName' => $record->file_name]))
                                        ->send();

                                    return null;
                                }
                            }),
                        Action::make('download')
                            ->label(__('media::fields.download_file'))
                            ->icon('heroicon-m-arrow-down-tray')
                            ->visible(fn() => config('media.modal.resource.show_download_button', true))
                            ->action(function (Media $record) {
                                return response()->download(
                                    $record->getPath(),
                                    $record->file_name,
                                    ['Content-Type' => $record->mime_type]
                                );
                            }),
                    ]),
            ])
            ->filters([
                SelectFilter::make('mime_type')
                    ->label(__('media::fields.mime_type'))
                    ->options([
                        'image' => __('media::fields.images'),
                        'video' => __('media::fields.videos'),
                        'audio' => __('media::fields.audios'),
                        'document' => __('media::fields.documents'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'image' => $query->where('mime_type', 'like', 'image/%'),
                            'video' => $query->where('mime_type', 'like', 'video/%'),
                            'audio' => $query->where('mime_type', 'like', 'audio/%'),
                            'document' => $query->where(function ($query) {
                                    $query->where('mime_type', 'like', 'application/%')
                                    ->orWhere('mime_type', 'like', 'text/%');
                                }),
                            default => $query,
                        };
                    }),

                SelectFilter::make('uploader')
                    ->label(__('media::fields.uploaded_by'))
                    ->options(function () {
                        $uploaderTypes = Media::query()
                            ->distinct()
                            ->whereNotNull('uploader_type')
                            ->pluck('uploader_type')
                            ->toArray();

                        $options = [];

                        foreach ($uploaderTypes as $type) {
                            /** @var \Illuminate\Database\Eloquent\Collection<int, Media> $mediaItems */
                            $mediaItems = Media::query()
                                ->where('uploader_type', $type)
                                ->whereNotNull('uploader_id')
                                ->with('uploader')
                                ->get();

                            /** @var array<string, string> $uploaders */
                            $uploaders = $mediaItems
                                ->map(function (Media $media): ?array {
                                    $uploader = $media->uploader;
                                    if ($uploader && method_exists($uploader, 'getName')) {
                                        return [
                                            'id' => $media->uploader_type . '::' . $media->uploader_id,
                                            'name' => $uploader->getName(),
                                        ];
                                    }
                                    if ($uploader && isset($uploader->name)) {
                                        return [
                                            'id' => $media->uploader_type . '::' . $media->uploader_id,
                                            'name' => $uploader->name,
                                        ];
                                    }

                                    return null;
                                })
                                ->filter()
                                ->unique(fn(array $item): string => $item['id'])
                                ->pluck('name', 'id')
                                ->toArray();

                            if (!empty($uploaders)) {
                                $typeName = class_basename($type);
                                $options[$typeName] = $uploaders;
                            }
                        }

                        return $options;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        $parts = explode('::', $data['value']);
                        if (count($parts) !== 2) {
                            return $query;
                        }

                        return $query
                            ->where('uploader_type', $parts[0])
                            ->where('uploader_id', $parts[1]);
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('date')
                    ->label(__('media::fields.uploaded_at'))
                    ->options([
                        'today' => __('media::fields.today'),
                        'week' => __('media::fields.week'),
                        'month' => __('media::fields.month'),
                        'year' => __('media::fields.year'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        switch ($data['value']) {
                            case 'today':
                                $query->whereDate('created_at', today());
                                break;
                            case 'week':
                                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                                break;
                            case 'month':
                                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                                break;
                            case 'year':
                                $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                                break;
                        }

                        return $query;
                    }),
                SelectFilter::make('collection_name')
                    ->label(__('media::fields.collection'))
                    ->options(function () {
                        return Media::query()
                            ->distinct()
                            ->pluck('collection_name', 'collection_name')
                            ->filter()
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return $query->where('collection_name', $data['value']);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([30, 60, 90])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
                'xl' => 5,
                '2xl' => 6,
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
