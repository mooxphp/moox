<?php

namespace Moox\Training\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use Moox\Training\Filters\DateRangeFilter;
use Moox\Training\Models\Training;
use Moox\Training\Resources\TrainingResource\Pages;

class TrainingResource extends Resource
{
    protected static ?string $model = Training::class;

    protected static ?string $navigationIcon = 'gmdi-school';

    protected static ?string $navigationGroup = 'Trainings';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('slug')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('duration')
                    ->toggleable()
                    ->searchable(true, null, true),
                Tables\Columns\TextColumn::make('link')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('due_at')
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('cycle')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_id')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('trainingType.title')
                    ->toggleable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('trainingable_id')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('trainingable_type')
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
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function ($records, Tables\Actions\DeleteBulkAction $action) {
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

    public static function getRelations(): array
    {
        return [
            TrainingResource\RelationManagers\TrainingInvitationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainings::route('/'),
            'create' => Pages\CreateTraining::route('/create'),
            'view' => Pages\ViewTraining::route('/{record}'),
            'edit' => Pages\EditTraining::route('/{record}/edit'),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return config('trainings.navigation_sort');
    }
}
