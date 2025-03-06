<?php

namespace Moox\Tag\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Moox\Tag\Models\TagTranslation;
use Moox\Tag\Resources\TagTranslationResource\Pages;

class TagTranslationResource extends Resource
{
    protected static ?string $model = TagTranslation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tag_id')
                    ->label('Tag ID')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('locale')
                    ->label('Language')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),
                //
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('locale')
                    ->label('Language')
                    ->options([
                        'en' => 'English',
                        'fr' => 'French',
                        'es' => 'Spanish',
                        'de' => 'German',
                        'it' => 'Italian',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTagTranslations::route('/'),
            'create' => Pages\CreateTagTranslation::route('/create'),
            'edit' => Pages\EditTagTranslation::route('/{record}/edit'),
        ];
    }
}
