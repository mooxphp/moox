<?php

declare(strict_types=1);

namespace Moox\Restore\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Restore\Models\RestoreDestination;
use Moox\Restore\Resources\RestoreDestinationResource\Pages\CreateRestoreDestination;
use Moox\Restore\Resources\RestoreDestinationResource\Pages\EditRestoreDestination;
use Moox\Restore\Resources\RestoreDestinationResource\Pages\ListRestoreDestinations;
use Spatie\BackupServer\Models\Source;

class RestoreDestinationResource extends BaseItemResource
{
    protected static ?string $model = RestoreDestination::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-pin-end-o';

    public static function enableView(): bool
    {
        return false;
    }

    public static function getCancelAction(): Action
    {
        return parent::getCancelAction()
            ->url(fn (): string => static::getUrl('index'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('host')
                                ->label(__('restore::translations.host'))
                                ->placeholder(__('restore::translations.host-placeholder'))
                                ->rules(['max:255', 'string'])
                                ->required()
                                ->placeholder('Host'),
                            Select::make('source_id')
                                ->label(__('restore::translations.source'))
                                ->relationship('source', 'host')
                                ->options(Source::query()->pluck('host', 'id'))
                                ->required(),
                            TextInput::make('env_data.APP_URL')
                                ->label(__('restore::translations.app-url'))
                                ->placeholder(__('restore::translations.app-url-placeholder'))
                                ->required(),
                            TextInput::make('env_data.APP_NAME')
                                ->label(__('restore::translations.app-name'))
                                ->placeholder(__('restore::translations.app-name-placeholder')),
                            TextInput::make('env_data.DB_DATABASE')
                                ->label(__('restore::translations.db-name'))
                                ->placeholder(__('restore::translations.db-name-placeholder')),
                            TextInput::make('env_data.DB_USERNAME')
                                ->label(__('restore::translations.db-username'))
                                ->placeholder(__('restore::translations.db-username-placeholder')),
                            TextInput::make('env_data.DB_PASSWORD')
                                ->label(__('restore::translations.db-password'))
                                ->placeholder('************')
                                ->password()
                                ->revealable(),
                            TextInput::make('env_data.REDIS_QUEUE')
                                ->label(__('restore::translations.redis-queue'))
                                ->placeholder(__('restore::translations.redis-queue-placeholder')),
                            TextInput::make('env_data.REDIS_DB')
                                ->label(__('restore::translations.redis-db')),
                            TextInput::make('env_data.REDIS_CACHE_DB')
                                ->label(__('restore::translations.redis-cache-db')),
                        ])
                        ->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source.name')
                    ->label(__('restore::translations.source-name')),
                TextColumn::make('host')
                    ->label(__('restore::translations.host'))
                    ->toggleable()
                    ->url(function ($record) {
                        $host = $record->host;

                        return "https://$host";
                    })
                    ->openUrlInNewTab()
                    ->icon('gmdi-link')
                    ->iconPosition(IconPosition::After)
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label(__('restore::translations.created-at'))
                    ->dateTime(),
            ])
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestoreDestinations::route('/'),
            'create' => CreateRestoreDestination::route('/create'),
            'edit' => EditRestoreDestination::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return config('restore.resources.destination.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('restore.resources.destination.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('restore.resources.destination.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('restore.resources.destination.single');
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
        return config('restore.navigation_group');
    }
}
