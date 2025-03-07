<?php

namespace Moox\Restore\Resources\RestoreDestinationResource\Pages;

use Moox\Restore\Resources\RestoreDestinationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRestoreDestinations extends ListRecords
{
    protected static string $resource = RestoreDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
