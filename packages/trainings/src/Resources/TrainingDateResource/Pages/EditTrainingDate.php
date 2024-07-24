<?php

namespace Moox\Training\Resources\TrainingDateResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Training\Resources\TrainingDateResource;

class EditTrainingDate extends EditRecord
{
    protected static string $resource = TrainingDateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
