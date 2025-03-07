<?php

namespace Moox\BackupServerUi\Resources\BackupResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\BackupServerUi\Resources\BackupResource;

class ViewBackup extends ViewRecord
{
    protected static string $resource = BackupResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
