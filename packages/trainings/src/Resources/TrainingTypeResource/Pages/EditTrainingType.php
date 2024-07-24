<?php

namespace Moox\Training\Resources\TrainingTypeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Moox\Training\Resources\TrainingTypeResource;

class EditTrainingType extends EditRecord
{
    protected static string $resource = TrainingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()
            ->action(function ($record, DeleteAction $action) {
                try {
                    $record->delete();
                    Notification::make()
                        ->title('Training Type Deleted')
                        ->body('The type was deleted successfully.')
                        ->success()
                        ->send();
                } catch (QueryException $exception) {
                    if ($exception->getCode() === '23000') {
                        Notification::make()
                            ->title('Cannot Delete Training Type')
                            ->body('One or more type have associated trainings and cannot be deleted.')
                            ->danger()
                            ->send();
                    } else {
                        throw $exception;
                    }
                }
            }),
        ];
    }
}
