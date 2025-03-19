<?php

namespace Moox\Training\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\QueryException;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Training\Filters\DateRangeFilter;
use Moox\Training\Models\TrainingType;
use Moox\Training\Resources\TrainingTypeResource\Pages\CreateTrainingType;
use Moox\Training\Resources\TrainingTypeResource\Pages\EditTrainingType;
use Moox\Training\Resources\TrainingTypeResource\Pages\ListTrainingTypes;
use Moox\Training\Resources\TrainingTypeResource\Pages\ViewTrainingType;
use Moox\Training\Resources\TrainingTypeResource\RelationManagers\TrainingsRelationManager;
use Override;

class TrainingTypeResource extends Resource
{
    use HasResourceTabs;

    protected static ?string $model = TrainingType::class;

    protected static ?string $navigationIcon = 'gmdi-assignment';

    protected static ?string $navigationGroup = 'Trainings';

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
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
            ])
            ->filters([DateRangeFilter::make('created_at')])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->action(function ($records, DeleteBulkAction $action): void {
                        foreach ($records as $record) {
                            try {
                                $record->delete();
                                Notification::make()
                                    ->title('Training Types Deleted')
                                    ->body('The types were deleted successfully.')
                                    ->success()
                                    ->send();
                            } catch (QueryException $exception) {
                                if ($exception->getCode() === '23000') {
                                    Notification::make()
                                        ->title('Cannot Delete Training Types')
                                        ->body('One or more type have associated trainings and cannot be deleted.')
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
            TrainingsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTrainingTypes::route('/'),
            'create' => CreateTrainingType::route('/create'),
            'view' => ViewTrainingType::route('/{record}'),
            'edit' => EditTrainingType::route('/{record}/edit'),
        ];
    }
}
