<?php

namespace Moox\Training\Resources\TrainingDateResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Training\Resources\TrainingDateResource;

class ViewTrainingDate extends ViewRecord
{
    protected static string $resource = TrainingDateResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
