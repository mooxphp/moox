<?php

namespace Moox\Training\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Training\Filters\DateRangeFilter;
use Moox\Training\Models\TrainingDate;
use Moox\Training\Resources\TrainingDateResource\Pages;

class TrainingDateResource extends Resource
{
    protected static ?string $model = TrainingDate::class;

    protected static ?string $navigationIcon = 'gmdi-date-range';

    protected static ?string $navigationGroup = 'heco Schulungen';

    protected static ?string $recordTitleAttribute = 'link';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    Select::make('training_invitation_id')
                        ->rules(['exists:training_invitations,id'])
                        ->required()
                        ->relationship('trainingInvitation', 'title')
                        ->searchable()
                        ->placeholder('Training Invitation')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('begin')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('Begin')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('end')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('End')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('type')
                        ->rules(['in:onsite,teams,webex,slack,zoom'])
                        ->required()
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
                        ->nullable()
                        ->placeholder('Link')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('location')
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->placeholder('Location')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('min_participants')
                        ->rules(['numeric'])
                        ->nullable()
                        ->numeric()
                        ->placeholder('Min Participants')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('max_participants')
                        ->rules(['numeric'])
                        ->nullable()
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
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('trainingInvitation.title')
                    ->toggleable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('begin')
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('end')
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('type')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('link')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('location')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('min_participants')
                    ->toggleable()
                    ->searchable(true, null, true),
                Tables\Columns\TextColumn::make('max_participants')
                    ->toggleable()
                    ->searchable(true, null, true),
                Tables\Columns\TextColumn::make('sent_at')
                    ->toggleable()
                    ->dateTime(),
            ])
            ->filters([
                DateRangeFilter::make('created_at'),

                SelectFilter::make('training_invitation_id')
                    ->relationship('trainingInvitation', 'title')
                    ->indicator('TrainingInvitation')
                    ->multiple()
                    ->label('TrainingInvitation'),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingDates::route('/'),
            'create' => Pages\CreateTrainingDate::route('/create'),
            'view' => Pages\ViewTrainingDate::route('/{record}'),
            'edit' => Pages\EditTrainingDate::route('/{record}/edit'),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return config('trainings.navigation_sort') + 2;
    }
}
