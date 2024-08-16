<?php

namespace Moox\Training\Resources;

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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use Moox\Training\Filters\DateRangeFilter;
use Moox\Training\Models\TrainingInvitation;
use Moox\Training\Resources\TrainingInvitationResource\Pages;

class TrainingInvitationResource extends Resource
{
    protected static ?string $model = TrainingInvitation::class;

    protected static ?string $navigationIcon = 'gmdi-insert-invitation';

    protected static ?string $navigationGroup = 'heco Schulungen';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
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

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('training.title')
                    ->toggleable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('title')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('slug')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('content')
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
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
            ->actions([EditAction::make()])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function ($records, Tables\Actions\DeleteBulkAction $action) {
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

    public static function getRelations(): array
    {
        return [
            TrainingInvitationResource\RelationManagers\TrainingDatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingInvitations::route('/'),
            'create' => Pages\CreateTrainingInvitation::route('/create'),
            'view' => Pages\ViewTrainingInvitation::route('/{record}'),
            'edit' => Pages\EditTrainingInvitation::route('/{record}/edit'),
            'prepare' => Pages\PrepareTrainingInvitation::route('/{record}/prepare'),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return config('trainings.navigation_sort') + 1;
    }
}
