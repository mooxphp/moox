<?php

namespace Moox\Training\Resources\TrainingResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\QueryException;
use Moox\Training\Resources\TrainingResource;

class ViewTraining extends ViewRecord
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->action(function ($record, DeleteAction $action) {
                    try {
                        $record->delete();
                        Notification::make()
                            ->title('Training Deleted')
                            ->body('The training was deleted successfully.')
                            ->success()
                            ->send();
                    } catch (QueryException $exception) {
                        if ($exception->getCode() === '23000') {
                            Notification::make()
                                ->title('Cannot Delete Training')
                                ->body('The training has associated invitations and cannot be deleted.')
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
