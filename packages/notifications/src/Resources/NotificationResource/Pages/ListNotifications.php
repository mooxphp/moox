<?php

namespace Moox\Notification\Resources\NotificationResource\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Traits\HasDynamicTabs;
use Filament\Resources\Pages\ListRecords;
use Moox\Notification\Models\Notification;
use Moox\Notification\Resources\NotificationResource;

class ListNotifications extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('notifications.notifications.tabs', Notification::class);
    }
}
