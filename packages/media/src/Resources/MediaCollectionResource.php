<?php

namespace Moox\Media\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaCollectionResource\Pages\CreateMediaCollection;
use Moox\Media\Resources\MediaCollectionResource\Pages\EditMediaCollection;
use Moox\Media\Resources\MediaCollectionResource\Pages\ListMediaCollections;

class MediaCollectionResource extends Resource
{
    use BaseInResource;

    protected static ?string $model = MediaCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

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

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label(__('media::fields.collection_name'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
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
                    ->counts('media')
                    ->sortable(),
            ])
            ->actions([
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
        return static::getModel()::count();
    }
}
