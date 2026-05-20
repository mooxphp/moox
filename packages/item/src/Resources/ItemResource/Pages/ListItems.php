<?php

declare(strict_types=1);

namespace Moox\Item\Resources\ItemResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Item\Models\Item;
use Moox\Item\Resources\ItemResource;

class ListItems extends BaseListItems
{
    use HasListPageTabs;

    protected static string $resource = ItemResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('item.resources.item.tabs', Item::class);
    }
}
