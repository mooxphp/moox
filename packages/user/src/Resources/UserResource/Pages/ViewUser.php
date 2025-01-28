<?php

namespace Moox\User\Resources\UserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\User\Resources\UserResource;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }

}
