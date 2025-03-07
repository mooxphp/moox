<?php

namespace Moox\BackupServerUi\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\BackupServerUi\Resources\DestinationResource\Pages;
use Moox\BackupServerUi\Resources\DestinationResource\RelationManagers\BackupsRelationManager;
use Spatie\BackupServer\Models\Destination;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $navigationIcon = 'heroicon-s-arrow-right-end-on-rectangle';

    protected static ?string $navigationLabel = 'Destination';

    protected static ?string $pluralNavigationLabel = 'Destinations';

    protected static ?string $navigationGroup = 'Backup server';

    protected static ?int $priority = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    Hidden::make('status')
                        ->required()
                        ->default('active'),

                    TextInput::make('name')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('disk_name')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Disk Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('keep_all_backups_for_days')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Keep All Backups For Days')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('keep_daily_backups_for_days')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Keep Daily Backups For Days')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('keep_weekly_backups_for_weeks')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Keep Weekly Backups For Weeks')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('keep_monthly_backups_for_months')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Keep Monthly Backups For Months')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('keep_yearly_backups_for_years')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Keep Yearly Backups For Years')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make(
                        'delete_oldest_backups_when_using_more_megabytes_than'
                    )
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder(
                            'Delete Oldest Backups When Using More Megabytes Than'
                        )
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make(
                        'healthy_maximum_backup_age_in_days_per_source'
                    )
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder(
                            'Healthy Maximum Backup Age In Days Per Source'
                        )
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('healthy_maximum_storage_in_mb_per_source')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder(
                            'Healthy Maximum Storage In Mb Per Source'
                        )
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('healthy_maximum_storage_in_mb')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Healthy Maximum Storage In Mb')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('healthy_maximum_inode_usage_percentage')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Healthy Maximum Inode Usage Percentage')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('name')
                    ->label('Destination')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('disk_name')
                    ->label('Disk')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('keep_all_backups_for_days')
                    ->label('Keep all')
                    ->toggleable(),
                TextColumn::make('keep_daily_backups_for_days')
                    ->label('Keep days')
                    ->toggleable(),
                TextColumn::make('keep_weekly_backups_for_weeks')
                    ->label('Keep weeks')
                    ->toggleable(),
                TextColumn::make('keep_monthly_backups_for_months')
                    ->label('Keep months')
                    ->toggleable(),
                TextColumn::make('keep_yearly_backups_for_years')
                    ->label('Keep years')
                    ->toggleable(),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            BackupsRelationManager::class,
            // Todo: SourcesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'view' => Pages\ViewDestination::route('/{record}'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}
