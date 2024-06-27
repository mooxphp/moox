<?php

namespace Moox\Notification\Resources\NotificationResource\Pages;

use Moox\User\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Notification\Resources\NotificationResource;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;
}
