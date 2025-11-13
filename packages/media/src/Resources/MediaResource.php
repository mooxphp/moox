<?php

namespace Moox\Media\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaResource\Pages;
use Moox\Media\Resources\MediaResource\Pages\ListMedia;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class MediaResource extends Resource
{
    use BaseInResource;

    protected static ?string $model = Media::class;

    protected static \BackedEnum|string|null $navigationIcon = 'gmdi-collections';

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
            SpatieMediaLibraryFileUpload::make('image')
                ->columnSpanFull()
                ->collection(function ($record) {
                    $mediaCollection = $record->collection_name;

                    if ($record->getFirstMedia()) {
                        return $record->getFirstMedia()->collection_name;
                    }

                    return $mediaCollection;
                })
                ->imageEditor(fn ($record) => ! $record->write_protected)
                ->downloadable(fn ($record) => ! $record->write_protected)
                ->deletable(fn ($record) => ! $record->write_protected)
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

                            $oldMedia = null;
                            $oldMediaId = null;

                            if ($record instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media) {
                                $oldMedia = $record;
                                $oldMediaId = $record->id;
                            } else {
                                $oldMedia = $record->getFirstMedia();
                                $oldMediaId = $oldMedia?->id;
                            }

                            $originalFileName = pathinfo($oldMedia?->file_name, PATHINFO_FILENAME);
                            $newFileName = pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME);

                            $isEdit = preg_match('/-v\d+$/', $newFileName);

                            $usables = [];
                            if ($oldMediaId && ! $isEdit) {
                                $usables = \DB::table('media_usables')
                                    ->where('media_id', $oldMediaId)
                                    ->get()
                                    ->map(function ($item) {
                                        return (array) $item;
                                    })
                                    ->toArray();
                            }

                            if ($oldMediaId && ! $isEdit) {
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

                            if (! $isEdit) {
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

                                            if (! is_array($jsonData)) {
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
                    Grid::make(4)
                        ->schema([
                            TextEntry::make('file_name')
                                ->label(__('media::fields.file_name'))
                                ->state(fn ($record) => $record->file_name),

                            TextEntry::make('mime_type')
                                ->label(__('media::fields.mime_type'))
                                ->state(fn ($record) => $record->getReadableMimeType()),

                            TextEntry::make('size')
                                ->label(__('media::fields.size'))
                                ->state(function ($record) {
                                    $bytes = $record->size;
                                    $units = ['B', 'KB', 'MB', 'GB'];
                                    $i = 0;

                                    while ($bytes >= 1024 && $i < count($units) - 1) {
                                        $bytes /= 1024;
                                        $i++;
                                    }

                                    return number_format($bytes, 2).' '.$units[$i];
                                }),

                            TextEntry::make('dimensions')
                                ->label(__('media::fields.dimensions'))
                                ->state(function ($record) {
                                    $dimensions = $record->getCustomProperty('dimensions');
                                    if (! $dimensions) {
                                        return '-';
                                    }

                                    return "{$dimensions['width']} × {$dimensions['height']} Pixel";
                                })
                                ->visible(fn ($record) => str_starts_with($record->mime_type ?? '', 'image/')),

                            TextEntry::make('created_at')
                                ->label(__('media::fields.created_at'))
                                ->state(fn ($record) => $record->created_at?->format('d.m.Y H:i')),

                            TextEntry::make('updated_at')
                                ->label(__('media::fields.updated_at'))
                                ->state(fn ($record) => $record->updated_at?->format('d.m.Y H:i')),

                            TextEntry::make('uploaded_by')
                                ->label(__('media::fields.uploaded_by'))
                                ->state(function ($record) {
                                    if (! $record->uploader) {
                                        return '-';
                                    }

                                    return $record->uploader->name;
                                }),

                            TextEntry::make('usage')
                                ->label(__('media::fields.usage'))
                                ->state(function ($record) {
                                    $usages = \DB::table('media_usables')
                                        ->where('media_id', $record->id)
                                        ->get();

                                    if ($usages->isEmpty()) {
                                        return __('media::fields.not_used');
                                    }

                                    return view('media::components.usage-button', [
                                        'record' => $record,
                                        'usages' => $usages,
                                    ]);
                                }),

                            Select::make('media_collection_id')
                                ->label(__('media::fields.collection'))
                                ->disabled(fn ($record) => $record?->getOriginal('write_protected'))
                                ->options(
                                    MediaCollection::whereHas('translations', function ($query) {
                                        $query->where('locale', app()->getLocale());
                                    })->get()->pluck('name', 'id')->filter()->toArray()
                                )
                                ->default(fn ($record) => $record->media_collection_id)
                                ->afterStateUpdated(function ($state, $record) {
                                    if ($state !== $record->media_collection_id) {
                                        $record->media_collection_id = $state;
                                        $record->save();
                                    }
                                }),
                        ]),
                ])
                ->columnSpanFull(),

            Section::make(__('media::fields.metadata'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('media::fields.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateHydrated(function ($component, $state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                $component->state($translation?->name ?? '');
                            }
                        })
                        ->afterStateUpdated(function ($state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                if ($translation) {
                                    $translation->name = $state;
                                    $translation->save();
                                }
                            }
                        })
                        ->disabled(fn ($record) => $record?->getOriginal('write_protected')),

                    TextInput::make('title')
                        ->label(__('media::fields.title'))
                        ->live(onBlur: true)
                        ->afterStateHydrated(function ($component, $state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                $component->state($translation?->title ?? '');
                            }
                        })
                        ->afterStateUpdated(function ($state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                if ($translation) {
                                    $translation->title = $state;
                                    $translation->save();
                                }
                            }
                        })
                        ->disabled(fn ($record) => $record?->getOriginal('write_protected')),

                    TextInput::make('alt')
                        ->label(__('media::fields.alt_text'))
                        ->live(onBlur: true)
                        ->afterStateHydrated(function ($component, $state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                $component->state($translation?->alt ?? '');
                            }
                        })
                        ->afterStateUpdated(function ($state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                if ($translation) {
                                    $translation->alt = $state;
                                    $translation->save();
                                }
                            }
                        })
                        ->disabled(fn ($record) => $record?->getOriginal('write_protected')),

                    Textarea::make('description')
                        ->label(__('media::fields.description'))
                        ->live(onBlur: true)
                        ->afterStateHydrated(function ($component, $state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                $component->state($translation?->description ?? '');
                            }
                        })
                        ->afterStateUpdated(function ($state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                if ($translation) {
                                    $translation->description = $state;
                                    $translation->save();
                                }
                            }
                        })
                        ->disabled(fn ($record) => $record?->getOriginal('write_protected')),
                ])
                ->columnSpanFull()
                ->columns(2)
                ->collapsed(),

            Section::make(__('media::fields.internal_note'))
                ->schema([
                    TextInput::make('internal_note')
                        ->live(onBlur: true)
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, $state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                $component->state($translation?->internal_note ?? '');
                            }
                        })
                        ->afterStateUpdated(function ($state, $record, $livewire) {
                            if ($record && method_exists($record, 'translations')) {
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();
                                if ($translation) {
                                    $translation->internal_note = $state;
                                    $translation->save();
                                }
                            }
                        })
                        ->disabled(fn ($record) => $record?->getOriginal('write_protected')),
                ])
                ->columnSpanFull()
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
                            $style = $baseStyle.'opacity: 0.5;';
                            if (in_array($record->id, $livewire->selected)) {
                                $style = $baseStyle.'outline: 4px solid rgb(59 130 246); opacity: 1;';
                            }

                            return [
                                'style' => $style.'; border-radius: 0.5rem; cursor: pointer;',
                                'wire:click.stop' => "\$set('selected', ".
                                    (in_array($record->id, $livewire->selected)
                                        ? json_encode(array_values(array_diff($livewire->selected, [$record->id])))
                                        : json_encode(array_merge($livewire->selected, [$record->id]))
                                    ).')',
                            ];
                        }

                        return [
                            'style' => $baseStyle.'; border-radius: 0.5rem; cursor: pointer;',
                            'x-on:click' => '$wire.call("mountAction", "edit", { record: '.$record->id.' })',
                        ];
                    })
                    ->tooltip(function ($record, $livewire) {
                        $currentLang = $livewire->lang;

                        if (method_exists($record, 'translations')) {
                            $translation = $record->translations()
                                ->where('locale', $currentLang)
                                ->whereNotNull('title')
                                ->where('title', '!=', '')
                                ->first();

                            if ($translation) {
                                return $translation->title;
                            }
                        }

                        return pathinfo($record->file_name, PATHINFO_FILENAME);
                    })
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
                    ->visible(fn ($record) => $record && ! str_starts_with($record->mime_type ?? '', 'image/'))
                    ->extraAttributes(function ($record, $livewire) {
                        $baseStyle = 'margin-top: 10px; word-break: break-all;';
                        if ($livewire->isSelecting) {
                            return [
                                'style' => $baseStyle.'; border-radius: 0.5rem; cursor: pointer;',
                                'wire:click.stop' => "\$set('selected', ".
                                    (in_array($record->id, $livewire->selected)
                                        ? json_encode(array_values(array_diff($livewire->selected, [$record->id])))
                                        : json_encode(array_merge($livewire->selected, [$record->id]))
                                    ).')',
                            ];
                        }

                        return [
                            'style' => $baseStyle.'; border-radius: 0.5rem; cursor: pointer;',
                            'x-on:click' => '$wire.call("mountAction", "edit", { record: '.$record->id.' })',
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
                        'style' => $baseStyle.'; border-radius: 0.5rem; cursor: pointer;',
                        'x-on:click' => '$wire.call("mountAction", "edit", { record: '.$record->id.' })',
                    ];
                })
                ->tooltip(function ($record, $livewire) {
                    $currentLang = $livewire->lang;

                    if (method_exists($record, 'translations')) {
                        $translation = $record->translations()
                            ->where('locale', $currentLang)
                            ->whereNotNull('title')
                            ->where('title', '!=', '')
                            ->first();

                        if ($translation) {
                            return $translation->title;
                        }
                    }

                    return pathinfo($record->file_name, PATHINFO_FILENAME);
                })
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
                ->searchable()
                ->formatStateUsing(function ($record, $livewire) {
                    $lang = $livewire->lang ?? app()->getLocale();

                    if (method_exists($record, 'translations')) {
                        $translation = $record->translations()->where('locale', $lang)->first();
                        if ($translation && ! empty($translation->name)) {
                            return $translation->name;
                        }
                    }

                    return $record->name ?: '-';
                })
                ->extraAttributes(fn ($record) => [
                    'style' => $record->translations()->where('locale', request()->get('lang', app()->getLocale()))->whereNotNull('name')->exists()
                        ? ''
                        : 'color: var(--gray-500);',
                ]);

            $columns[] = TextColumn::make('collection.name')
                ->label(__('media::fields.collection'))
                ->searchable();

            $columns[] = TextColumn::make('mime_type')
                ->label(__('media::fields.mime_type'))
                ->searchable()
                ->formatStateUsing(fn ($record) => $record->getReadableMimeType());

            $columns[] = TextColumn::make('uploader_type')
                ->label(__('media::fields.uploaded_by'))
                ->formatStateUsing(fn ($record) => $record->uploader?->name);

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
                        : $count.' '.trans_choice('media::fields.link|links', $count);
                });
        }

        return $table
            ->columns($columns)
            ->contentGrid(fn ($livewire) => $livewire->isGridView ? [
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
                    ->icon(fn ($livewire) => $livewire->isSelecting ? 'heroicon-m-x-mark' : 'heroicon-m-square-2-stack')
                    ->color(fn ($livewire) => $livewire->isSelecting ? 'gray' : 'primary')
                    ->visible(fn ($livewire) => $livewire->isGridView)
                    ->action(function ($livewire) {
                        $livewire->isSelecting = ! $livewire->isSelecting;
                        $livewire->selected = [];

                        if (! $livewire->isSelecting) {
                            return redirect(static::getUrl('index'));
                        }
                    }),

                Action::make('deleteSelected')
                    ->label(function ($livewire) {
                        $count = count($livewire->selected);

                        return $count > 0
                            ? __('media::fields.delete_selected')." ({$count} ".trans_choice('media::fields.file|files', $count).')'
                            : __('media::fields.delete_selected');
                    })
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(function ($livewire) {
                        $count = count($livewire->selected);

                        return __('media::fields.delete_selected')." ({$count} ".trans_choice('media::fields.file|files', $count).')';
                    })
                    ->modalDescription(__('media::fields.delete_confirmation'))
                    ->modalSubmitActionLabel(__('media::fields.yes_delete'))
                    ->modalCancelActionLabel(__('media::fields.cancel'))
                    ->visible(fn ($livewire) => $livewire->isGridView && $livewire->isSelecting && ! empty($livewire->selected))
                    ->action(function ($livewire) {
                        $successCount = 0;
                        $errorCount = 0;
                        $protectedCount = 0;

                        foreach ($livewire->selected as $id) {
                            try {
                                $media = Media::find($id);
                                if (! $media) {
                                    continue;
                                }

                                if (! auth()->user()->can('delete', $media)) {
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
                                Log::error('Media deletion failed: '.$e->getMessage(), [
                                    'media_id' => $id,
                                ]);
                                $errorCount++;
                            }
                        }

                        if ($successCount > 0) {
                            Notification::make()
                                ->success()
                                ->title($successCount.' '.trans_choice('media::fields.file_deleted|files_deleted', $successCount))
                                ->send();
                        }

                        if ($protectedCount > 0) {
                            Notification::make()
                                ->warning()
                                ->title(__('media::fields.protected_skipped'))
                                ->body($protectedCount.' '.trans_choice('media::fields.protected_file_skipped|protected_files_skipped', $protectedCount))
                                ->persistent()
                                ->send();
                        }

                        if ($errorCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('media::fields.delete_error'))
                                ->body($errorCount.' '.trans_choice('media::fields.file_could_not_be_deleted|files_could_not_be_deleted', $errorCount))
                                ->persistent()
                                ->send();
                        }

                        $livewire->isSelecting = ! $livewire->isSelecting;
                        $livewire->selected = [];

                        if (! $livewire->isSelecting) {
                            return redirect(static::getUrl('index'));
                        }
                    }),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label(__('media::fields.delete_selected'))
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(function (Collection $records) {
                        return __('media::fields.delete_selected').' ('.$records->count().' '.trans_choice('media::fields.file|files', $records->count()).')';
                    })
                    ->modalDescription(__('media::fields.delete_confirmation'))
                    ->modalSubmitActionLabel(__('media::fields.yes_delete'))
                    ->modalCancelActionLabel(__('media::fields.cancel'))
                    ->visible(fn ($livewire) => ! $livewire->isGridView)
                    ->action(function (Collection $records) {
                        $successCount = 0;
                        $errorCount = 0;
                        $protectedCount = 0;

                        foreach ($records as $media) {
                            try {
                                if (! auth()->user()->can('delete', $media)) {
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
                                Log::error('Media deletion failed: '.$e->getMessage(), [
                                    'media_id' => $media->id,
                                ]);
                                $errorCount++;
                            }
                        }

                        if ($successCount > 0) {
                            Notification::make()
                                ->success()
                                ->title($successCount.' '.trans_choice('media::fields.file_deleted|files_deleted', $successCount))
                                ->send();
                        }

                        if ($protectedCount > 0) {
                            Notification::make()
                                ->warning()
                                ->title(__('media::fields.protected_skipped'))
                                ->body($protectedCount.' '.trans_choice('media::fields.protected_file_skipped|protected_files_skipped', $protectedCount))
                                ->persistent()
                                ->send();
                        }

                        if ($errorCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title(__('media::fields.delete_error'))
                                ->body($errorCount.' '.trans_choice('media::fields.file_could_not_be_deleted|files_could_not_be_deleted', $errorCount))
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->checkIfRecordIsSelectableUsing(fn ($record) => ! $record->getOriginal('write_protected'))
            ->actions([
                EditAction::make()
                    ->icon('')
                    ->label('')
                    ->slideOver()
                    ->modalHeading(function ($record, $livewire) {
                        $lang = $livewire->lang ?? app()->getLocale();

                        if (method_exists($record, 'translations')) {
                            $translation = $record->translations()->where('locale', $lang)->first();
                            if ($translation && ! empty($translation->name)) {
                                return $translation->name;
                            }
                        }

                        return $record->name ?: 'No name';
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('media::fields.cancel'))
                    ->authorize('view')
                    ->extraModalFooterActions([
                        Action::make('save_translation')
                            ->label(__('media::fields.save_translation'))
                            ->color('success')
                            ->icon('heroicon-m-language')
                            ->visible(function ($record, $livewire) {
                                if (! $record || ! method_exists($record, 'translations')) {
                                    return false;
                                }
                                $lang = $livewire->lang ?? app()->getLocale();
                                $translation = $record->translations()->where('locale', $lang)->first();

                                return ! $translation;
                            })
                            ->action(function ($record, $livewire) {
                                $livewire->saveTranslationFromForm($record->id);
                            }),
                        Action::make('delete')
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
                                    $description[] = '<div style="color: #9f1239;  font-weight: 500; font-size: 1.125rem; margin-bottom: 0.5rem;">'.
                                        __('media::fields.warning_file_has_links', [
                                            'count' => $usages,
                                            'links' => trans_choice('media::fields.link|links', $usages),
                                        ]).
                                        '</div>';

                                    $description[] = '<div style="background-color: #f8d4da; color: #9f1239; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">'.
                                        __('media::fields.delete_linked_warning').
                                        '</div>';
                                }

                                $description[] = __('media::fields.delete_file_description');

                                return new \Illuminate\Support\HtmlString(implode("\n", $description));
                            })
                            ->modalSubmitActionLabel(__('media::fields.yes_delete'))
                            ->modalCancelActionLabel(__('media::fields.cancel'))
                            ->hidden(fn (Media $record) => ! auth()->user()->can('delete', $record) || $record->getOriginal('write_protected'))
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
                                    Log::error('Media deletion failed: '.$e->getMessage(), [
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
                            ->visible(fn () => config('media.modal.resource.show_download_button', true))
                            ->action(function (Media $record) {
                                return response()->download(
                                    $record->getPath(),
                                    $record->file_name,
                                    ['Content-Type' => $record->mime_type]
                                );
                            }),
                    ]),
            ])
            ->deferFilters(false)
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
                        if (! $data['value']) {
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
                                $options[$typeName] = $uploaders;
                            }
                        }

                        return $options;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
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
                        if (! $data['value']) {
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
                        if (! $data['value']) {
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
