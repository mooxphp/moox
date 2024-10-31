<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Blub;
use Camya\Filament\Forms\Components\TitleWithSlugInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlubResource extends Resource
{
    protected static ?string $model = Blub::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TitleWithSlugInput::make(
                fieldTitle: 'title',
                fieldSlug: 'slug'
            )->label('Title'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlubs::route('/'),
            'create' => Pages\CreateBlub::route('/create'),
            'edit' => Pages\EditBlub::route('/{record}/edit'),
            'view' => Pages\ViewBlub::route('/{record}'),
        ];
    }
}
