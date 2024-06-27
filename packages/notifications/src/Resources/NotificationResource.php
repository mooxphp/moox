<?php

namespace Moox\Notification\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Moox\Notification\Models\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
use Moox\Notification\Resources\NotificationResource\Pages\EditNotification;
use Moox\Notification\Resources\NotificationResource\Pages\ViewNotification;
use Moox\Notification\Resources\NotificationResource\Pages\ListNotifications;
use Moox\Notification\Resources\NotificationResource\Pages\CreateNotification;
use Moox\Notification\Resources\NotificationResource\Pages\ListNoticiations;
use Moox\Notification\Resources\NotificationResource\Widgets\NotificationWidgets;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('type'),
                TextInput::make('notifiable_type'),
                TextInput::make('notifiable_id'),
                TextInput::make('data'),
                DatePicker::make('read_at'),




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type'),
                TextColumn::make('notifiable_type'),
                TextColumn::make('notifiable_id'),
                TextColumn::make('data'),
                TextColumn::make('read_at'),

            ])
            ->defaultSort('type', 'desc')
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
            'index' => ListNotifications::route('/'),
            'create' => CreateNotification::route('/create'),
            'view' => ViewNotification::route('/{record}'),
            'edit' => EditNotification::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            NotificationWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('notifications::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notifications::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('notifications::translations.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('notifications::translations.breadcrumb');
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
        return __('notifications::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('notifications.navigation_sort');
    }
}
