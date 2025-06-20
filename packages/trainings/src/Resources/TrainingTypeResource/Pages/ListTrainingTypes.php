<?php

namespace Moox\Training\Resources\TrainingTypeResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Training\Resources\TrainingTypeResource;
use Moox\Training\Traits\HasDescendingOrder;

class ListTrainingTypes extends ListRecords
{
    use HasDescendingOrder;

    protected static string $resource = TrainingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
