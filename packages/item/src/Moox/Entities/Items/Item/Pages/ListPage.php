<?php

namespace Moox\Item\Moox\Entities\Items\Item\Pages;

use Moox\Core\Entities\Items\Item\Pages\MooxListPage;
use Moox\Item\Moox\Entities\Items\ItemResource;

class ListPage extends MooxListPage
{
    protected static string $resource = ItemResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.simple-item.tabs', ItemResource::class);
    }
}
