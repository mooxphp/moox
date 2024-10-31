<?php

declare(strict_types=1);

namespace App\Preview\Filament\Resources;

use Filament\Resources\Resource;
use App\Models\Blub;
use Filament\Forms\Form;
use Filament\Tables\Table;

class BlubResource extends Resource
{
    protected static ?string $model = Blub::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        
        return $form->schema([
            
        ]);
    }

    public static function table(Table $table): Table
    {
        
        return $table
            ->columns([])
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
