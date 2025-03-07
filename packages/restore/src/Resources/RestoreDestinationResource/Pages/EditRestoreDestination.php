<?php

namespace Moox\Restore\Resources\RestoreDestinationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Moox\Restore\Resources\RestoreDestinationResource;

class EditRestoreDestination extends EditRecord
{
    protected static string $resource = RestoreDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
