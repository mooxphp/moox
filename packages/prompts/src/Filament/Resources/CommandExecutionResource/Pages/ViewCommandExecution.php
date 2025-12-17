<?php

namespace Moox\Prompts\Filament\Resources\CommandExecutionResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Prompts\Filament\Resources\CommandExecutionResource;

class ViewCommandExecution extends ViewRecord
{
    protected static string $resource = CommandExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
