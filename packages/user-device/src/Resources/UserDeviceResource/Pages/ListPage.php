<?php

namespace Moox\UserDevice\Resources\UserDeviceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;
use Moox\UserDevice\Resources\UserDeviceResource\Widgets\UserDeviceWidgets;

class ListPage extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = UserDeviceResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            // TODO: Widgets
            //UserDeviceWidgets::class,
        ];
    }

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
        return $this->getDynamicTabs('user-device.resources.devices.tabs', UserDevice::class);
    }
}
