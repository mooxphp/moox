<?php

namespace Moox\Bpmn\Resources\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Bpmn\Resources\BpmnResource;

class EditBpmn extends EditRecord
{
    protected static string $resource = BpmnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
