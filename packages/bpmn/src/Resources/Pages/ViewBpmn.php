<?php

namespace Moox\Bpmn\Resources\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Bpmn\Resources\BpmnResource;

class ViewBpmn extends ViewRecord
{
    protected static string $resource = BpmnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
