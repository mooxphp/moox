<?php

namespace Moox\UserSession\Resources\UserSessionResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\UserSession\Resources\UserSessionResource;

class ViewPage extends ViewRecord
{
    protected static string $resource = UserSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
