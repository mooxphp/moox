<?php

namespace Moox\Training\Resources\TrainingResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class TrainingInvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'trainingInvitations';

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
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

                RichEditor::make('content')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Content')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                DateTimePicker::make('sent_at')
                    ->rules(['date'])
                    ->placeholder('Sent At')
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
                TextColumn::make('training.title')->limit(50),
                TextColumn::make('title')->limit(50),
                TextColumn::make('slug')->limit(50),
                TextColumn::make('content')->limit(50),
                TextColumn::make('sent_at')->dateTime(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
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

                SelectFilter::make('training_id')
                    ->multiple()
                    ->relationship('training', 'title'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }
}
