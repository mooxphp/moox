<?php

namespace Moox\Media\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Localization\Models\Localization;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaCollectionResource\Pages\CreateMediaCollection;
use Moox\Media\Resources\MediaCollectionResource\Pages\EditMediaCollection;
use Moox\Media\Resources\MediaCollectionResource\Pages\ListMediaCollections;

class MediaCollectionResource extends Resource
{
    use BaseInResource;

    protected static ?string $model = MediaCollection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return config('media.collections.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return config('media.collections.resource.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('media.collections.resource.navigation_group');
    }

    public static function getNavigationParentItem(): ?string
    {
        return config('media.model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('media::fields.collection_name'))
                ->required()
                ->maxLength(255)
                ->rule(function ($record) {
                    return function ($attribute, $value, $fail) use ($record) {
                        $locale = app()->getLocale();
                        $exists = MediaCollection::whereTranslation('name', $value, $locale)
                            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                            ->exists();
                        if ($exists) {
                            $fail(__('media::fields.collection_name_already_exists'));
                        }
                    };
                }),

            TextInput::make('description')
                ->label(__('media::fields.collection_description'))
                ->maxLength(255)
                ->formatStateUsing(function ($state, $record, $livewire) {
                    if (! $record || ! method_exists($record, 'getTranslation')) {
                        return $state;
                    }

                    $lang = $livewire->lang ?? app()->getLocale();
                    $translation = $record->getTranslation($lang, false);

                    return $translation ? $translation->description : $state;
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('media::fields.collection_name'))
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('translations', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->state(function ($record, $livewire) {
                        $currentLang = $livewire->lang ?? app()->getLocale();

                        $translation = $record->translations()->where('locale', $currentLang)->first();
                        if ($translation && $translation->name) {
                            return $translation->name;
                        }

                        if (class_exists(Localization::class)) {
                            $defaultLocale = Localization::query()
                                ->where('is_default', true)
                                ->where('is_active_admin', true)
                                ->with('language')
                                ->first();

                            if ($defaultLocale) {
                                $defaultLang = $defaultLocale->language->alpha2;
                                $fallbackTranslation = $record->translations()->where('locale', $defaultLang)->first();
                                if ($fallbackTranslation && $fallbackTranslation->name) {
                                    return $fallbackTranslation->name.' ('.$defaultLang.')';
                                }
                            }
                        }

                        $anyTranslation = $record->translations()->whereNotNull('name')->first();
                        if ($anyTranslation && $anyTranslation->name) {
                            return $anyTranslation->name.' ('.$anyTranslation->locale.')';
                        }

                        return 'No name available';
                    })
                    ->extraAttributes(function ($record, $livewire) {
                        $currentLang = $livewire->lang ?? app()->getLocale();
                        $translation = $record->translations()->where('locale', $currentLang)->first();

                        if ($translation && $translation->name) {
                            return [];
                        }

                        return ['style' => 'color: var(--gray-500);'];
                    }),
                TextColumn::make('description')
                    ->label(__('media::fields.collection_description'))
                    ->searchable()
                    ->formatStateUsing(function ($record, $livewire) {
                        $currentLang = $livewire->lang ?? app()->getLocale();

                        $translation = $record->translations()->where('locale', $currentLang)->first();
                        if ($translation && $translation->description) {
                            return $translation->description;
                        }

                        return '';
                    }),
                TextColumn::make('media_count')
                    ->label(__('media::fields.media_count'))
                    ->getStateUsing(function ($record) {
                        return Media::query()->where('media_collection_id', $record->id)->count();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(function (MediaCollection $record, $livewire) {
                        $currentLang = $livewire->lang ?? app()->getLocale();
                        $translation = $record->translations()->where('locale', $currentLang)->first();

                        return $translation ? __('filament-actions::edit.single.label') : __('core::core.create');
                    })
                    ->color(function (MediaCollection $record, $livewire) {
                        $currentLang = $livewire->lang ?? app()->getLocale();
                        $translation = $record->translations()->where('locale', $currentLang)->first();

                        return $translation ? 'primary' : 'success';
                    })
                    ->icon(function (MediaCollection $record, $livewire) {
                        $currentLang = $livewire->lang ?? app()->getLocale();
                        $translation = $record->translations()->where('locale', $currentLang)->first();

                        return $translation ? 'heroicon-m-pencil-square' : 'heroicon-m-plus';
                    })
                    ->url(function (MediaCollection $record, $livewire) {
                        $currentLang = $livewire->lang ?? app()->getLocale();

                        return static::getUrl('edit', ['record' => $record, 'lang' => $currentLang]);
                    }),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(function (MediaCollection $record) {
                        return __('media::fields.delete_collection_with_media_heading', ['name' => $record->name, 'count' => $record->media()->count()]);
                    })
                    ->modalDescription(function (MediaCollection $record) {
                        $count = $record->media()->count();
                        if ($count > 0) {
                            return __('media::fields.delete_collection_with_media_warning', [
                                'count' => $count,
                                'files' => trans_choice('media::fields.file|files', $count),
                                'uncategorized' => __('media::fields.uncategorized'),
                            ]);
                        }

                        return __('media::fields.delete_collection_warning');
                    })
                    ->modalSubmitActionLabel(__('media::fields.delete_collection'))
                    ->modalCancelActionLabel(__('media::fields.cancel'))
                    ->disabled(function (MediaCollection $record) {
                        return $record->name === __('media::fields.uncategorized') ||
                            $record->media()->where('write_protected', true)->exists();
                    }),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaCollections::route('/'),
            'create' => CreateMediaCollection::route('/create'),
            'edit' => EditMediaCollection::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }
}
