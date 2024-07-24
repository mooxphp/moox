<?php

namespace Moox\Training\Resources\TrainingInvitationResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Moox\Training\Resources\TrainingInvitationResource;

class PrepareTrainingInvitation extends EditRecord
{
    protected static string $resource = TrainingInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendInvitations')
                ->label('Send Invitations')
                ->action(function () {
                    // Send invitation job
                }),
            DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return parent::form($form);
    }
}
