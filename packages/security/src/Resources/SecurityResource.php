<?php

namespace Moox\Security\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Security\Models\Security;
use Moox\Security\Resources\ResetPasswordResource\Pages\ListPage;
use Moox\Security\Resources\ResetPasswordResource\Widgets\ResetPasswordWidgets;

class SecurityResource extends Resource
{
    protected static ?string $modelLabel = 'Reset Tokens';

    protected static ?string $model = Security::class;

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
                TextColumn::make('email')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
                //                TextColumn::make('user_type')
                //                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
            'index' => ListPage::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ResetPasswordWidgets::class,
        ];
    }
}
