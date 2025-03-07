<?php

namespace Moox\BackupServerUi\Resources\BackupLogItemResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\BackupServerUi\Resources\BackupLogItemResource;

class ListBackupLogItems extends ListRecords
{
    protected static string $resource = BackupLogItemResource::class;
}
