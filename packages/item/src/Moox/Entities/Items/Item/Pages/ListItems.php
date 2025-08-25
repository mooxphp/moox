<?php

namespace Moox\Item\Moox\Entities\Items\Item\Pages;

use Moox\Item\Models\Item;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;

class ListItems extends BaseListItems
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('item.resources.item.tabs', Item::class);
    }
}
