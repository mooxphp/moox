<?php

namespace Moox\Security\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Security\Models\ResetPassword;
use Moox\Security\Resources\ResetPasswordResource\Pages\ListPage;
use Moox\Security\Resources\ResetPasswordResource\Widgets\ResetPasswordWidgets;

class ResetPasswordResource extends Resource
{
    protected static ?string $modelLabel = 'Reset Tokens';

    protected static ?string $model = ResetPassword::class;

    protected static ?string $navigationIcon = 'gmdi-token';

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
                    ->label(__('core::user.email'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('core::core.created_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->sortable(),
            ])
            ->actions([
                //
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

    public static function getModelLabel(): string
    {
        return config('security.resources.security.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('security.resources.security.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('security.resources.security.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('security.resources.security.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('security.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('security.navigation_sort') + 5;
    }
}
