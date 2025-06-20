<?php

namespace Moox\Training\Resources\TrainingInvitationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Training\Resources\TrainingInvitationResource;

class ViewTrainingInvitation extends ViewRecord
{
    protected static string $resource = TrainingInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
