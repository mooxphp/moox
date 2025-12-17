<?php

namespace Moox\Bpmn\Resources\Bpmns\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Bpmn\Resources\Bpmns\BpmnResource;

class ListBpmns extends ListRecords
{
    protected static string $resource = BpmnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
