<?php

namespace Moox\UserDevice\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Config;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource\Pages\ListPage;
use Moox\UserDevice\Resources\UserDeviceResource\Pages\ViewPage;
use Moox\UserDevice\Resources\UserDeviceResource\Widgets\UserDeviceWidgets;
use Override;

class UserDeviceResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = UserDevice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-devices-o';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('core::core.title'))
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('core::core.slug'))
                    ->maxLength(255),
                DateTimePicker::make('updated_at')
                    ->label(__('core::core.updated_at')),
                DateTimePicker::make('created_at')
                    ->label(__('core::core.created_at')),
                TextInput::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->required(),
                // TODO: should we make this editable? Then this needs to be a select field
                /*
                Select::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->options(function () {
                        $models = Config::get('user-device.user_models', []);

                        return array_flip($models);
                    })
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state) {
                        $set('user_id', null);
                    })
                    ->required(),
                */
                TextInput::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->required(),
                // TODO: Not implemented yet, must be editable then
                // TODO: Is misleading, should be activated, enabled or similar, because active would better be recently been in use
                /*
                Toggle::make('active')
                    ->label(__('core::core.active'))
                    ->required(),
                */
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('platform')
                    ->label(__('core::sync.platform'))
                    ->icon(fn ($record): string => match ($record->platform) {
                        'Mobile' => 'heroicon-o-device-phone-mobile',
                        'Desktop' => 'heroicon-o-computer-desktop',
                        default => 'heroicon-o-computer-desktop',
                    }),
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->getStateUsing(fn ($record) => optional($record->user)->name ?? 'unknown')
                    ->sortable(),

                // TODO: Not implemented yet, must be editable then
                // TODO: Is misleading, should be activated, enabled or similar, because active would better be recently been in use
                /*
                IconColumn::make('active')
                    ->label(__('core::core.active'))
                    ->toggleable()
                    ->boolean(),
                */
                TextColumn::make('updated_at')
                    ->label(__('core::core.updated_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('core::core.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('title', 'desc')
            ->recordActions([
                ViewAction::make(),
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
            'index' => ListPage::route('/'),
            // 'view' => ViewPage::route('/{record}'),
        ];
    }

    #[Override]
    public static function getWidgets(): array
    {
        return [
            // TODO: Implement widgets
            // UserDeviceWidgets::class,
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('user-device.resources.devices.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('user-device.resources.devices.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('user-device.resources.devices.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('user-device.resources.devices.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('user-device.navigation_group');
    }
}
