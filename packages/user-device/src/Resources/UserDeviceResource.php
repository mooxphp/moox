<?php

namespace Moox\UserDevice\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource\Pages\ListPage;
use Moox\UserDevice\Resources\UserDeviceResource\Widgets\UserDeviceWidgets;

class UserDeviceResource extends Resource
{
    protected static ?string $model = UserDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->maxLength(255),
                DateTimePicker::make('created_at'),
                Toggle::make('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('user-device::translations.title'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('user-device::translations.created_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('active')
                    ->label(__('user-device::translations.active'))
                    ->sortable(),
            ])
            ->defaultSort('title', 'desc')
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
            UserDeviceWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('user-device::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('user-device::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('user-device::translations.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('user-device::translations.breadcrumb');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return __('user-device::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('user-device.navigation_sort');
    }
}
