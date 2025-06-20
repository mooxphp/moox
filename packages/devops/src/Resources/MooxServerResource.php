<?php

namespace Moox\Devops\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Moox\Devops\Jobs\RebootServerJob;
use Moox\Devops\Models\MooxServer;
use Moox\Devops\Resources\MooxServerResource\Pages\ListPage;
use Moox\Devops\Resources\MooxServerResource\Widgets\MooxServerWidgets;

class MooxServerResource extends Resource
{
    protected static ?string $model = MooxServer::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('forge_id')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('ip_address')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('type')
                    ->maxLength(255),
                TextInput::make('provider')
                    ->maxLength(255),
                TextInput::make('region')
                    ->maxLength(255),
                TextInput::make('ubuntu_ver')
                    ->maxLength(255),
                TextInput::make('db_status')
                    ->maxLength(255),
                TextInput::make('redis_status')
                    ->maxLength(255),
                TextInput::make('php_version')
                    ->maxLength(255),
                Toggle::make('is_ready')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('devops::translations.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('forge_id')
                    ->label(__('devops::translations.forge_id'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label(__('devops::translations.ip_address'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('devops::translations.type'))
                    ->sortable(),
                TextColumn::make('provider')
                    ->label(__('devops::translations.provider'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('region')
                    ->label(__('devops::translations.region'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('ubuntu_version')
                    ->label(__('devops::translations.ubuntu_version'))
                    ->sortable(),
                TextColumn::make('php_version')
                    ->label(__('devops::translations.php_version'))
                    ->sortable(),
            ])
            ->defaultSort('name', 'desc')
            ->recordActions([
                Action::make('reboot')
                    ->label('Reboot')
                    ->action(function ($record) {
                        RebootServerJob::dispatch($record, auth()->user(), null);
                        Notification::make()
                            ->title('Booting server '.$record->name)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reboot')
                    ->modalDescription('Are you sure you\'d like to reboot this server?'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                BulkAction::make('reboot')
                    ->requiresConfirmation()
                    ->action(
                        fn (Collection $records) => $records->each(
                            fn ($record) => RebootServerJob::dispatch(MooxServer::find($record->getKey()), auth()->user(), null))),
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
            // MooxServerWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('devops::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('devops::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('devops::translations.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('devops::translations.breadcrumb');
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
        return __('devops::translations.navigation_group');
    }
}
