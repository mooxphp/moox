<?php

declare(strict_types=1);

namespace Moox\Item\Moox\Entities\Items\Item\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Item\Models\Item;

class ListItems extends BaseListItems
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('item.resources.item.tabs', Item::class);
    }
}
