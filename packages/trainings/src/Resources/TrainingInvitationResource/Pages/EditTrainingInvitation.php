<?php

namespace Moox\Training\Resources\TrainingInvitationResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Moox\Training\Jobs\SendInvitations;
use Moox\Training\Resources\TrainingInvitationResource;

class EditTrainingInvitation extends EditRecord
{
    protected static string $resource = TrainingInvitationResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (config('trainings.send_invitations_action') !== false) {
            $actions[] = Action::make('sendInvitations')
                ->label('Send Invitations')
                ->action(function () {
                    SendInvitations::dispatch($this->record->getKey());
                })
                ->requiresConfirmation()
                ->color('primary');
        }

        $actions[] = DeleteAction::make()
            ->action(function ($record) {
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
            });

        return $actions;
    }
}
