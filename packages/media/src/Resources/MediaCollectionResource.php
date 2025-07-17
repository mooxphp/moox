<?php

namespace Moox\Media\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
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
            Select::make('extend_existing_collection')
                ->label(__('media::fields.extend_existing_collection'))
                ->placeholder(__('media::fields.create_new_collection'))
                ->options(function () {
                    $currentLocale = app()->getLocale();

                    $collections = MediaCollection::whereHas('translations', function ($query) use ($currentLocale) {
                        $query->where('locale', '!=', $currentLocale);
                    })->get();

                    $options = [];
                    foreach ($collections as $collection) {
                        $translation = $collection->translations->where('locale', '!=', $currentLocale)->first();
                        if ($translation && is_string($translation->name) && $translation->name !== '') {
                            $options[$collection->id] = $translation->name." ({$translation->locale})";
                        }
                    }

                    return $options;
                })
                ->searchable()
                ->hidden(function () {
                    $currentLocale = app()->getLocale();

                    return ! MediaCollection::whereHas('translations', function ($query) use ($currentLocale) {
                        $query->where('locale', '!=', $currentLocale);
                    })->exists();
                }),
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
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('media::fields.collection_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('media::fields.collection_description'))
                    ->searchable(),
                TextColumn::make('media_count')
                    ->label(__('media::fields.media_count'))
                    ->getStateUsing(function ($record) {
                        return Media::where('media_collection_id', $record->id)->count();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
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
        return static::getModel()::whereHas('translations', function ($query) {
            $query->where('locale', app()->getLocale());
        })->count();
    }
}
