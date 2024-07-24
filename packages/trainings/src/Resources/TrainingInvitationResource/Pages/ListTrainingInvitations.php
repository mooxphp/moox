<?php

namespace Moox\Training\Resources\TrainingInvitationResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Training\Jobs\SendInvitationRequests;
use Moox\Training\Resources\TrainingInvitationResource;
use Moox\Training\Traits\HasDescendingOrder;

class ListTrainingInvitations extends ListRecords
{
    use HasDescendingOrder;

    protected static string $resource = TrainingInvitationResource::class;

    protected function getHeaderActions(): array
    {
        if (config('trainings.collect_invitation_action') === false) {
            return [];
        }

        return [
            Action::make('collectInvitations')
                ->label('Collect Invitations')
                ->action(function () {
                    SendInvitationRequests::dispatch();
                })
                ->requiresConfirmation()
                ->color('primary'),
        ];
    }
}
