<?php

namespace Moox\BackupServerUi\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\BackupServerUi\Models\BackupLogItem;
use Moox\BackupServerUi\Resources\BackupLogItemResource\Pages\ListBackupLogItems;
use Moox\BackupServerUi\Resources\BackupLogItemResource\Pages\ViewBackupLogItem;

class BackupLogItemResource extends Resource
{
    protected static ?string $model = BackupLogItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-bars-4';

    protected static ?string $navigationLabel = 'Backup Log';

    protected static ?string $pluralNavigationLabel = 'Backup Logs';

    protected static string|\UnitEnum|null $navigationGroup = 'Backup server';

    protected static ?string $recordTitleAttribute = 'Log Entry';

    protected static ?int $priority = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    Select::make('source_id')
                        ->rules(['exists:backup_server_sources,id'])
                        ->required()
                        ->relationship('source', 'name')
                        ->placeholder('Source')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('backup_id')
                        ->rules(['exists:backup_server_backups,id'])
                        ->required()
                        ->relationship('backup', 'status')
                        ->placeholder('Status')
                        ->label('Status')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('destination_id')
                        ->rules(['exists:backup_server_destinations,id'])
                        ->required()
                        ->relationship('destination', 'name')
                        ->placeholder('Destination')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('task')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Task')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('level')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Level')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('message')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Message')
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
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('backup.status')
                    ->label('Status')
                    ->icon(fn (string $state): string => match ($state) {
                        'failed' => 'heroicon-o-x-circle',
                        'completed' => 'heroicon-o-check-circle',
                        'pending' => 'heroicon-o-play',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->colors([
                        'secondary',
                        'danger' => 'failed',
                        'warning' => 'pending',
                        'success' => 'completed',
                    ]),
                TextColumn::make('source.name')
                    ->label('Source')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('destination.name')
                    ->label('Destination')
                    ->searchable()
                    ->toggleable()
                    ->limit(50),
                TextColumn::make('task')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                IconColumn::make('level')
                    ->label('Level')
                    ->icon(fn (string $state): string => match ($state) {
                        'heroicon-o-question-mark-circle',
                        'error' => 'heroicon-o-exclamation-circle',
                        'warning' => 'heroicon-o-question-mark-circle',
                        'info' => 'heroicon-o-information-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->colors([
                        'secondary',
                        'danger' => 'error',
                        'warning' => 'warning',
                        'success' => 'info',
                    ]),
                TextColumn::make('message')
                    ->toggleable()
                    ->searchable()
                    ->limit(25),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->toggleable()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('source_id')
                    ->relationship('source', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Source'),

                SelectFilter::make('destination_id')
                    ->relationship('destination', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Destination'),

                SelectFilter::make('task')
                    ->options([
                        'backup' => 'Backup',
                        'cleanup' => 'Cleanup',
                        'prune' => 'Prune',
                        'monitor' => 'Monitor',
                    ])
                    ->label('Task'),

                SelectFilter::make('level')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ])
                    ->label('Level'),
            ])
            ->recordActions([ViewAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBackupLogItems::route('/'),
            'view' => ViewBackupLogItem::route('/{record}'),
        ];
    }
}
