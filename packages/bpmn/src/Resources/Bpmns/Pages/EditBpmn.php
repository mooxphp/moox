<?php

namespace Moox\Bpmn\Resources\Bpmns\Pages;


use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Bpmn\Resources\Bpmns\BpmnResource;

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
