<?php

namespace Moox\Notification\Resources\NotificationResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Moox\User\Resources\UserResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Notification\Resources\NotificationResource;

class ViewNotification extends ViewRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
