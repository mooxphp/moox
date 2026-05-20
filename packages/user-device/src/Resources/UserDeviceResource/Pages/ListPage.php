<?php

namespace Moox\UserDevice\Resources\UserDeviceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;
use Override;

class ListPage extends ListRecords
{
    use HasListPageTabs;

    public static string $resource = UserDeviceResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('core::device.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            // none by now
        ];
    }

    public function getTabs(): array
    {
        if (! UserDeviceResource::shouldShowTabsForUser(filament()->auth()->user())) {
            return [];
        }

        return $this->getDynamicTabs('user-device.resources.devices.tabs', UserDevice::class);
    }
}
