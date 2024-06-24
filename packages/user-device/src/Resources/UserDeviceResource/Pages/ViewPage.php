<?php

namespace Moox\UserDevice\Resources\UserDeviceResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\UserDevice\Resources\UserDeviceResource;

class ViewPage extends ViewRecord
{
    protected static string $resource = UserDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
