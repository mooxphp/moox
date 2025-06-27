<?php

namespace Moox\Training\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Moox\Training\Models\TrainingInvitation;
use Moox\Training\Resources\TrainingInvitationResource\Pages\CreateTrainingInvitation;
use Moox\Training\Resources\TrainingInvitationResource\Pages\EditTrainingInvitation;
use Moox\Training\Resources\TrainingInvitationResource\Pages\ListTrainingInvitations;
use Moox\Training\Resources\TrainingInvitationResource\Pages\PrepareTrainingInvitation;
use Moox\Training\Resources\TrainingInvitationResource\Pages\ViewTrainingInvitation;
use Moox\Training\Resources\TrainingInvitationResource\RelationManagers\TrainingDatesRelationManager;
use Override;

class TrainingInvitationResource extends Resource
{
    use HasResourceTabs;

    protected static ?string $model = TrainingInvitation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-insert-invitation';

    protected static string|\UnitEnum|null $navigationGroup = 'Trainings';

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    Select::make('training_id')
                        ->rules(['exists:trainings,id'])
                        ->required()
                        ->relationship('training', 'title')
                        ->searchable()
                        ->placeholder('Training')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

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

                    RichEditor::make('content')
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->placeholder('Content')
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
                TextColumn::make('training.title')
                    ->toggleable()
                    ->limit(50),
                TextColumn::make('title')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('slug')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('content')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('status')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
            ])
            ->filters([
                DateRangeFilter::make('created_at'),

                SelectFilter::make('training_id')
                    ->relationship('training', 'title')
                    ->indicator('Training')
                    ->multiple()
                    ->label('Training'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->action(function ($records, DeleteBulkAction $action): void {
                        foreach ($records as $record) {
                            try {
                                $record->delete();
                                Notification::make()
                                    ->title('Training Invitations Deleted')
                                    ->body('The invitations were deleted successfully.')
                                    ->success()
                                    ->send();
                            } catch (QueryException $exception) {
                                if ($exception->getCode() === '23000') {
                                    Notification::make()
                                        ->title('Cannot Delete Training Invitations')
                                        ->body('One or more invitations have associated training dates and cannot be deleted.')
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
            TrainingDatesRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTrainingInvitations::route('/'),
            'create' => CreateTrainingInvitation::route('/create'),
            'view' => ViewTrainingInvitation::route('/{record}'),
            'edit' => EditTrainingInvitation::route('/{record}/edit'),
            'prepare' => PrepareTrainingInvitation::route('/{record}/prepare'),
        ];
    }
}
