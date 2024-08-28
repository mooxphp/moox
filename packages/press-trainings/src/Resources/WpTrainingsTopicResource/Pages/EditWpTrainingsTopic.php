<?php

namespace Moox\PressTrainings\Resources\WpTrainingsTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressTrainings\Resources\WpTrainingsTopicResource;

class EditWpTrainingsTopic extends EditRecord
{
    protected static string $resource = WpTrainingsTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
