<?php

namespace Moox\Sync\Resources\SyncResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Sync\Resources\SyncResource;

class EditSync extends EditRecord
{
    protected static string $resource = SyncResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
