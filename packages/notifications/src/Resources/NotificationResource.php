<?php

namespace Moox\Notification\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Notification\Models\Notification;
use Moox\Notification\Resources\NotificationResource\Pages\CreateNotification;
use Moox\Notification\Resources\NotificationResource\Pages\EditNotification;
use Moox\Notification\Resources\NotificationResource\Pages\ListNotifications;
use Moox\Notification\Resources\NotificationResource\Pages\ViewNotification;
use Moox\Notification\Resources\NotificationResource\Widgets\NotificationWidgets;
use Override;

class NotificationResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = Notification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-notifications';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->label(__('core::core.type')),
                TextInput::make('notifiable_type')
                    ->label(__('core::notifications.notifiable_type')),
                TextInput::make('notifiable_id')
                    ->label(__('core::notifications.notifiable_id')),
                TextInput::make('data')
                    ->label(__('core::core.data')),
                DatePicker::make('read_at')
                    ->label(__('core::core.read_at')),

            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('core::core.type')),
                TextColumn::make('notifiable_type')
                    ->label(__('core::notifications.notifiable_type')),
                TextColumn::make('notifiable_id')
                    ->label(__('core::notifications.notifiable_id'))
                    ->sortable(),
                TextColumn::make('data')
                    ->label(__('core::core.data')),
                TextColumn::make('read_at')
                    ->label(__('core::core.read_at')),

            ])->searchable()
            ->defaultSort('type', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
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
            'index' => ListNotifications::route('/'),
            'create' => CreateNotification::route('/create'),
            'view' => ViewNotification::route('/{record}'),
            'edit' => EditNotification::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getWidgets(): array
    {
        return [
            NotificationWidgets::class,
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('notifications.notifications.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('notifications.notifications.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('notifications.notifications.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('notifications.notifications.single');
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
        return config('notifications.navigation_group');
    }
}
