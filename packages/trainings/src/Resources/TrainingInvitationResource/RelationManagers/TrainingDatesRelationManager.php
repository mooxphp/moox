<?php

namespace Moox\Training\Resources\TrainingInvitationResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class TrainingDatesRelationManager extends RelationManager
{
    protected static string $relationship = 'trainingDates';

    protected static ?string $recordTitleAttribute = 'link';

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 0])->schema([
                DateTimePicker::make('begin')
                    ->rules(['date'])
                    ->placeholder('Begin')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                DateTimePicker::make('end')
                    ->rules(['date'])
                    ->placeholder('End')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                Select::make('type')
                    ->rules(['in:onsite,teams,webex,slack,zoom'])
                    ->searchable()
                    ->options([
                        'onsite' => 'Onsite',
                        'teams' => 'Teams',
                        'webex' => 'Webex',
                        'slack' => 'Slack',
                        'zoom' => 'Zoom',
                    ])
                    ->placeholder('Type')
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

                TextInput::make('location')
                    ->rules(['max:255', 'string'])
                    ->placeholder('Location')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('min_participants')
                    ->rules(['numeric'])
                    ->numeric()
                    ->placeholder('Min Participants')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                TextInput::make('max_participants')
                    ->rules(['numeric'])
                    ->numeric()
                    ->placeholder('Max Participants')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 12,
                        'lg' => 12,
                    ]),

                DateTimePicker::make('sent_at')
                    ->rules(['date'])
                    ->nullable()
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
                TextColumn::make(
                    'trainingInvitation.title'
                )->limit(50),
                TextColumn::make('begin')->dateTime(),
                TextColumn::make('end')->dateTime(),
                TextColumn::make('type'),
                TextColumn::make('link')->limit(50),
                TextColumn::make('location')->limit(50),
                TextColumn::make('min_participants'),
                TextColumn::make('max_participants'),
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

                SelectFilter::make('training_invitation_id')
                    ->multiple()
                    ->relationship('trainingInvitation', 'title'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }
}
