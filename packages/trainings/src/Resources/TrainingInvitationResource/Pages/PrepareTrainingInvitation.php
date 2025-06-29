<?php

namespace Moox\Training\Resources\TrainingInvitationResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Moox\Training\Resources\TrainingInvitationResource;
use Override;

class PrepareTrainingInvitation extends EditRecord
{
    protected static string $resource = TrainingInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendInvitations')
                ->label('Send Invitations')
                ->action(function (): void {
                    // Send invitation job
                }),
            DeleteAction::make(),
        ];
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return parent::form($schema);
    }
}
