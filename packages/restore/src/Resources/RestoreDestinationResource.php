<?php

declare(strict_types=1);

namespace Moox\Restore\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Restore\Models\RestoreDestination;
use Moox\Restore\Resources\RestoreDestinationResource\Pages;
use Override;
use Spatie\BackupServer\Models\Source;

class RestoreDestinationResource extends Resource
{
    protected static ?string $model = RestoreDestination::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-pin-end-o';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('host')
                    ->label(__('restore::translations.host'))
                    ->placeholder(__('restore::translations.host-placeholder'))
                    ->rules(['max:255', 'string'])
                    ->required()
                    ->placeholder('Host')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
                Select::make('source_id')
                    ->label(__('restore::translations.source'))
                    ->relationship('source', 'host')
                    ->options(Source::query()->pluck('host', 'id'))
                    ->required()
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('env_data.APP_URL')
                    ->label(__('restore::translations.app-url'))
                    ->placeholder(__('restore::translations.app-url-placeholder'))
                    ->required()
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
                TextInput::make('env_data.APP_NAME')
                    ->label(__('restore::translations.app-name'))
                    ->placeholder(__('restore::translations.app-name-placeholder'))
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
                TextInput::make('env_data.DB_DATABASE')
                    ->label(__('restore::translations.db-name'))
                    ->placeholder(__('restore::translations.db-name-placeholder'))
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
                TextInput::make('env_data.DB_USERNAME')
                    ->label(__('restore::translations.db-username'))
                    ->placeholder(__('restore::translations.db-username-placeholder'))
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
                TextInput::make('env_data.DB_PASSWORD')
                    ->label(__('restore::translations.db-password'))
                    ->placeholder('************')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ])
                    ->password()
                    ->revealable(),

                TextInput::make('env_data.REDIS_QUEUE')
                    ->label(__('restore::translations.redis-queue'))
                    ->placeholder(__('restore::translations.redis-queue-placeholder'))
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
                TextInput::make('env_data.REDIS_DB')
                    ->label(__('restore::translations.redis-db'))
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
                TextInput::make('env_data.REDIS_CACHE_DB')
                    ->label(__('restore::translations.redis-cache-db'))
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source.name')
                    ->label(__('restore::translations.source-name')),

                TextColumn::make('host')
                    ->label(__('restore::translations.host'))
                    ->toggleable()
                    ->url(fn (RestoreDestination $record): string => 'https://'.$record->host)
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestoreDestinations::route('/'),
            'create' => Pages\CreateRestoreDestination::route('/create'),
            'edit' => Pages\EditRestoreDestination::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        $label = config('restore.resources.destination.single');

        return is_string($label) ? $label : 'Restore Destination';
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        $label = config('restore.resources.destination.plural');

        return is_string($label) ? $label : 'Restore Destinations';
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return static::getModelLabel();
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        $group = config('restore.navigation_group');

        return is_string($group) ? $group : null;
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        $sort = config('restore.navigation_sort');

        return (is_int($sort) ? $sort : 0) + 3;
    }
}
