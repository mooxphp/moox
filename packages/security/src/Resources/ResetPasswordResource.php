<?php

namespace Moox\Security\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Security\Models\ResetPassword;
use Moox\Security\Resources\ResetPasswordResource\Pages\ListPage;
use Moox\Security\Resources\ResetPasswordResource\Widgets\ResetPasswordWidgets;
use Override;

class ResetPasswordResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $modelLabel = 'Reset Tokens';

    protected static ?string $model = ResetPassword::class;

    protected static ?string $navigationIcon = 'gmdi-token';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    #[Override]
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

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
        ];
    }

    #[Override]
    public static function getWidgets(): array
    {
        return [
            ResetPasswordWidgets::class,
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('security.resources.security.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('security.resources.security.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('security.resources.security.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('security.resources.security.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('security.navigation_group');
    }
}
