<?php

namespace Moox\Training\Resources\TrainingDateResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Training\Resources\TrainingDateResource;
use Moox\Training\Traits\HasDescendingOrder;

class ListTrainingDates extends ListRecords
{
    use HasDescendingOrder;

    protected static string $resource = TrainingDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
