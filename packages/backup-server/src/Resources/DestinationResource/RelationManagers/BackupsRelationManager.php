<?php

namespace Moox\BackupServerUi\Resources\DestinationResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BackupsRelationManager extends RelationManager
{
    protected static string $relationship = 'backups';

    protected static ?string $recordTitleAttribute = 'status';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 0])->schema([
                TextInput::make('status')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Status')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                Select::make('source_id')
                    ->rules(['exists:backup_server_sources,id'])
                    ->relationship('source', 'name')
                    ->searchable()
                    ->placeholder('Source')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('disk_name')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Disk Name')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('path')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Path')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('size_in_kb')
                    ->rules(['max:255'])
                    ->placeholder('Size In Kb')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('real_size_in_kb')
                    ->rules(['max:255'])
                    ->placeholder('Real Size In Kb')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                DatePicker::make('completed_at')
                    ->rules(['date'])
                    ->placeholder('Completed At')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                RichEditor::make('rsync_summary')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Rsync Summary')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('rsync_time_in_seconds')
                    ->rules(['max:255'])
                    ->placeholder('Rsync Time In Seconds')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('rsync_current_transfer_speed')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Rsync Current Transfer Speed')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('rsync_average_transfer_speed_in_MB_per_second')
                    ->rules(['max:255', 'string'])
                    ->placeholder(
                        'Rsync Average Transfer Speed In Mb Per Second'
                    )
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')->limit(50),
                TextColumn::make('source.name')->limit(50),
                TextColumn::make('destination.name')->limit(50),
                TextColumn::make('disk_name')->limit(50),
                TextColumn::make('path')->limit(50),
                TextColumn::make('size_in_kb')->limit(50),
                TextColumn::make('real_size_in_kb')->limit(50),
                TextColumn::make('completed_at')->date(),
                TextColumn::make('rsync_summary')->limit(50),
                TextColumn::make('rsync_time_in_seconds')->limit(
                    50
                ),
                TextColumn::make(
                    'rsync_current_transfer_speed'
                )->limit(50),
                TextColumn::make(
                    'rsync_average_transfer_speed_in_MB_per_second'
                )->limit(50),
            ])
            ->filters([
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (
                                    Builder $query,
                                    $date
                                ): Builder => $query->whereDate(
                                    'created_at',
                                    '>=',
                                    $date
                                )
                            )
                            ->when(
                                $data['created_until'],
                                fn (
                                    Builder $query,
                                    $date
                                ): Builder => $query->whereDate(
                                    'created_at',
                                    '<=',
                                    $date
                                )
                            );
                    }),

                SelectFilter::make('source_id')
                    ->multiple()
                    ->relationship('source', 'name'),

                SelectFilter::make('destination_id')
                    ->multiple()
                    ->relationship('destination', 'name'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }
}
