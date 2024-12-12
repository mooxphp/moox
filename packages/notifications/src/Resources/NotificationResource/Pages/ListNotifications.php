<?php

namespace Moox\Notification\Resources\NotificationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\Notification\Models\Notification;
use Moox\Notification\Resources\NotificationResource;

class ListNotifications extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('notifications.resources.notifications.tabs', Notification::class);
    }
}
