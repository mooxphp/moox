<?php

namespace Moox\PressTrainings\Resources\WpTrainingResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressTrainings\Resources\WpTrainingResource;

class ViewWpTraining extends ViewRecord
{
    protected static string $resource = WpTrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
