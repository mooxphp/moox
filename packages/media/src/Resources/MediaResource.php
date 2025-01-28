<?php

namespace Moox\Media\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Expiry\Actions\CustomExpiryAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Moox\Media\Resources\MediaResource\Pages;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Moox\Media\Resources\MediaResource\Pages\ViewMedia;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;

class MediaResource extends Resource
{
    use BaseInResource, SingleSoftDeleteInResource, TabsInResource;

    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'gmdi-view-timeline-o';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('model.avatar_url')->sortable(),
            ])
            ->filters([

            ])
            ->actions([

            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
            // 'view' => Pages\ViewMedia::route('/{record}'),
            // 'edit' => Pages\EditMedia::route('/{record}/edit'),
        ];
    }


}
