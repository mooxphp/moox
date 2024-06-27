<?php

namespace Moox\Notification\Resources\NotificationResource\Pages;

use Filament\Actions\DeleteAction;
use Moox\User\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Notification\Resources\NotificationResource;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class EditNotification extends EditRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
