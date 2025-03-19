<?php

namespace Moox\Restore\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Restore\Models\RestoreDestination;
use Moox\Restore\Resources\RestoreDestinationResource\Pages;
use Spatie\BackupServer\Models\Source;

class RestoreDestinationResource extends Resource
{
    protected static ?string $model = RestoreDestination::class;

    protected static ?string $navigationIcon = 'gmdi-pin-end-o';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->options(Source::all()->pluck('host', 'id'))
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source.name') // Assuming you have a name field in the related model
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListRestoreDestinations::route('/'),
            'create' => Pages\CreateRestoreDestination::route('/create'),
            'edit' => Pages\EditRestoreDestination::route('/{record}/edit'),
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
