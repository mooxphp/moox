<?php

declare(strict_types=1);

namespace Moox\UserDevice\Resources\UserDeviceResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;
use Override;

class ListPage extends BaseListItems
{
    use HasListPageTabs;

    public static string $resource = UserDeviceResource::class;

    #[Override]
    public function getTitle(): string
    {
        return __('core::device.title');
    }

    public function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        if (! UserDeviceResource::shouldShowTabsForUser(filament()->auth()->user())) {
            return [];
        }

        return $this->getDynamicTabs('user-device.resources.devices.tabs', UserDevice::class);
    }
}
