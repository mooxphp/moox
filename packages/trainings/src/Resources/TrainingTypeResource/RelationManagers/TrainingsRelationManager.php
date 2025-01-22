<?php

namespace Moox\Training\Resources\TrainingTypeResource\RelationManagers;

use Override;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrainingsRelationManager extends RelationManager
{
    protected static string $relationship = 'trainings';

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(['default' => 0])->schema([
                TextInput::make('title')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Title')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('slug')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Slug')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                RichEditor::make('description')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Description')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('duration')
                    ->rules(['numeric'])
                    ->numeric()
                    ->placeholder('Duration')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('link')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Link')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                DateTimePicker::make('due_at')
                    ->rules(['date'])
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
                    ->placeholder('Source Id')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('trainingable_id')
                    ->rules(['max:255'])
                    ->placeholder('Trainingable Id')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('trainingable_type')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Trainingable Type')
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
                TextColumn::make('title')->limit(50),
                TextColumn::make('slug')->limit(50),
                TextColumn::make('description')->limit(50),
                TextColumn::make('duration'),
                TextColumn::make('link')->limit(50),
                TextColumn::make('due_at')->dateTime(),
                TextColumn::make('cycle'),
                TextColumn::make('source_id')->limit(50),
                TextColumn::make('trainingType.title')->limit(
                    50
                ),
                TextColumn::make('trainingable_id')->limit(50),
                TextColumn::make('trainingable_type')->limit(50),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
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
                        )),

                SelectFilter::make('training_type_id')
                    ->multiple()
                    ->relationship('trainingType', 'title'),
            ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
