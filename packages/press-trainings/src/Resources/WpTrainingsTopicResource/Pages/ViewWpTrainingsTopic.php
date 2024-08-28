<?php

namespace Moox\PressTrainings\Resources\WpTrainingsTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressTrainings\Resources\WpTrainingsTopicResource;

class ViewWpTrainingsTopic extends ViewRecord
{
    protected static string $resource = WpTrainingsTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
