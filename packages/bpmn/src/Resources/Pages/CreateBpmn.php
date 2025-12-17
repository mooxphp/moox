<?php

namespace Moox\Bpmn\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Bpmn\Resources\BpmnResource;

class CreateBpmn extends CreateRecord
{
    protected static string $resource = BpmnResource::class;
}
