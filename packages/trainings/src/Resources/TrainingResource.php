<?php

namespace Moox\Training\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Training\Filters\DateRangeFilter;
use Moox\Training\Models\Training;
use Moox\Training\Resources\TrainingResource\Pages\CreateTraining;
use Moox\Training\Resources\TrainingResource\Pages\EditTraining;
use Moox\Training\Resources\TrainingResource\Pages\ListTrainings;
use Moox\Training\Resources\TrainingResource\Pages\ViewTraining;
use Moox\Training\Resources\TrainingResource\RelationManagers\TrainingInvitationsRelationManager;
use Override;

class TrainingResource extends Resource
{
    use HasResourceTabs;

    protected static ?string $model = Training::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-school';

    protected static string|\UnitEnum|null $navigationGroup = 'Trainings';

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('title')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Title')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('slug')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Slug')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('description')
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->placeholder('Description')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('duration')
                        ->rules(['numeric'])
                        ->required()
                        ->numeric()
                        ->placeholder('Duration')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('link')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Link')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('due_at')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Due At')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('cycle')
                        ->rules([
                            'in:annually,half-yearly,quarterly,monthly,every 2 years,every 3 years,every 4 years,every 5 years',
                        ])
                        ->required()
                        ->searchable()
                        ->options([
                            'annually' => 'Annually',
                            'half-yearly' => 'Half yearly',
                            'quarterly' => 'Quarterly',
                            'monthly' => 'Monthly',
                            'every 2 years' => 'Every 2 years',
                            'every 3 years' => 'Every 3 years',
                            'every 4 years' => 'Every 4 years',
                            'every 5 years' => 'Every 5 years',
                        ])
                        ->placeholder('Cycle')
                        ->default('annually')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('source_id')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Source Id')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('training_type_id')
                        ->rules(['exists:training_types,id'])
                        ->required()
                        ->relationship('trainingType', 'title')
                        ->placeholder('Training Type')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('trainingable_id')
                        ->rules(['max:255'])
                        ->required()
                        ->placeholder('Trainingable Id')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('trainingable_type')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Trainingable Type')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                ]),
            ]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('title')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('slug')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('description')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('duration')
                    ->toggleable()
                    ->searchable(true, null, true),
                TextColumn::make('link')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('due_at')
                    ->toggleable()
                    ->dateTime(),
                TextColumn::make('cycle')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('source_id')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('trainingType.title')
                    ->toggleable()
                    ->limit(50),
                TextColumn::make('trainingable_id')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('trainingable_type')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->filters([
                DateRangeFilter::make('created_at'),

                SelectFilter::make('training_type_id')
                    ->relationship('trainingType', 'title')
                    ->indicator('TrainingType')
                    ->multiple()
                    ->label('TrainingType'),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->action(function ($records, DeleteBulkAction $action): void {
                        foreach ($records as $record) {
                            try {
                                $record->delete();
                                Notification::make()
                                    ->title('Trainings Deleted')
                                    ->body('The trainings were deleted successfully.')
                                    ->success()
                                    ->send();
                            } catch (QueryException $exception) {
                                if ($exception->getCode() === '23000') {
                                    Notification::make()
                                        ->title('Cannot Delete Trainings')
                                        ->body('One or more trainings have associated training invitations and cannot be deleted.')
                                        ->danger()
                                        ->send();
                                } else {
                                    throw $exception;
                                }
                            }
                        }
                    }),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            TrainingInvitationsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTrainings::route('/'),
            'create' => CreateTraining::route('/create'),
            'view' => ViewTraining::route('/{record}'),
            'edit' => EditTraining::route('/{record}/edit'),
        ];
    }
}
