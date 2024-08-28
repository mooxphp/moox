<?php

namespace Moox\PressTrainings\Resources\WpTrainingResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressTrainings\Resources\WpTrainingResource;

class EditWpTraining extends EditRecord
{
    protected static string $resource = WpTrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
