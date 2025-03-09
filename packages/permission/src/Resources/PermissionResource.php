<?php

namespace Moox\Permission\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Permission\Models\Permission;
use Moox\Permission\Resources\PermissionResource\Pages\ListPage;
use Moox\Permission\Resources\PermissionResource\Widgets\PermissionWidgets;
use Override;

class PermissionResource extends Resource
{
    use HasResourceTabs;

    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'gmdi-engineering';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->maxLength(255),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('finished_at'),
                Toggle::make('failed')
                    ->required(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('permission::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('permission::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('permission::translations.failed'))
                    ->sortable(),
            ])
            ->defaultSort('name', 'desc')
            ->actions([
                EditAction::make(),
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
            PermissionWidgets::class,
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('permission::translations.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('permission::translations.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('permission::translations.navigation_label');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return __('permission::translations.breadcrumb');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('permission::translations.navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('permission.navigation_sort');
    }
}
