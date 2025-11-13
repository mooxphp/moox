<?php

namespace Moox\Training\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-date-range';

    protected static string|\UnitEnum|null $navigationGroup = 'Trainings';

    protected static ?string $recordTitleAttribute = 'link';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('training_invitation_id')
                    ->rules(['exists:training_invitations,id'])
                    ->required()
                    ->relationship('trainingInvitation', 'title')
                    ->searchable()
                    ->placeholder('Training Invitation')
                    ->columnSpanFull(),

                DateTimePicker::make('begin')
                    ->rules(['date'])
                    ->required()
                    ->placeholder('Begin'),

                DateTimePicker::make('end')
                    ->rules(['date'])
                    ->required()
                    ->placeholder('End'),

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
                    ->placeholder('Type'),

                TextInput::make('link')
                    ->rules(['max:255', 'string'])
                    ->nullable()
                    ->placeholder('Link'),

                TextInput::make('location')
                    ->rules(['max:255', 'string'])
                    ->nullable()
                    ->placeholder('Location'),

                TextInput::make('min_participants')
                    ->rules(['numeric'])
                    ->nullable()
                    ->numeric()
                    ->placeholder('Min Participants'),

                TextInput::make('max_participants')
                    ->rules(['numeric'])
                    ->nullable()
                    ->numeric()
                    ->placeholder('Max Participants'),
                DateTimePicker::make('sent_at')
                    ->rules(['date'])
                    ->nullable()
                    ->placeholder('Sent At'),
            ]),
        ])->columns(1);
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
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
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
