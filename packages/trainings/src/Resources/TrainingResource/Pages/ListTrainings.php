<?php

namespace Moox\Training\Resources\TrainingResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Training\Resources\TrainingResource;
use Moox\Training\Traits\HasDescendingOrder;

class ListTrainings extends ListRecords
{
    use HasDescendingOrder;

    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        if (config('trainings.create_trainings_action') === false) {
            return [];
        }

        return [CreateAction::make()];
    }
}
