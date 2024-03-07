<?php

namespace Moox\Sync\Resources\SyncResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Sync\Resources\SyncResource;

class ViewSync extends ViewRecord
{
    protected static string $resource = SyncResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
