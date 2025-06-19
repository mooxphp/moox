<?php

namespace Moox\Restore\Resources\RestoreDestinationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Moox\Restore\Resources\RestoreDestinationResource;

class ListRestoreDestinations extends ListRecords
{
    protected static string $resource = RestoreDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
