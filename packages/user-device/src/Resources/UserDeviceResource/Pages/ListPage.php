<?php

declare(strict_types=1);

namespace Moox\UserDevice\Resources\UserDeviceResource\Pages;

use Filament\Schemas\Components\Tabs\Tab;
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

        $tabs = $this->getDynamicTabs('user-device.resources.devices.tabs', UserDevice::class);

        foreach (UserDeviceResource::panelTabDefinitions() as $panelId => $definition) {
            $userTypes = $definition['user_types'];

            $tabs[$panelId] = Tab::make($definition['label'])
                ->icon($definition['icon'])
                ->modifyQueryUsing(fn ($query) => $query->whereIn('user_type', $userTypes))
                ->badge(UserDevice::query()->whereIn('user_type', $userTypes)->count());
        }

        return $tabs;
    }
}
