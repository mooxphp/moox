<?php

namespace Moox\Training\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Training\Filters\DateRangeFilter;
use Moox\Training\Models\TrainingDate;
use Moox\Training\Resources\TrainingDateResource\Pages\CreateTrainingDate;
use Moox\Training\Resources\TrainingDateResource\Pages\EditTrainingDate;
use Moox\Training\Resources\TrainingDateResource\Pages\ListTrainingDates;
use Moox\Training\Resources\TrainingDateResource\Pages\ViewTrainingDate;
use Override;

class TrainingDateResource extends Resource
{
    use HasResourceTabs;

    protected static ?string $model = TrainingDate::class;

    protected static ?string $navigationIcon = 'gmdi-date-range';

    protected static ?string $navigationGroup = 'Trainings';

    protected static ?string $recordTitleAttribute = 'link';

    #[Override]
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

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('trainingInvitation.title')
                    ->toggleable()
                    ->limit(50),
                TextColumn::make('begin')
                    ->toggleable()
                    ->dateTime(),
                TextColumn::make('end')
                    ->toggleable()
                    ->dateTime(),
                TextColumn::make('type')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('link')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('location')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('min_participants')
                    ->toggleable()
                    ->searchable(true, null, true),
                TextColumn::make('max_participants')
                    ->toggleable()
                    ->searchable(true, null, true),
                TextColumn::make('sent_at')
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

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTrainingDates::route('/'),
            'create' => CreateTrainingDate::route('/create'),
            'view' => ViewTrainingDate::route('/{record}'),
            'edit' => EditTrainingDate::route('/{record}/edit'),
        ];
    }
}
