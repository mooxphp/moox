<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Entities\Items\Item\ItemResource;
use Moox\Core\Traits\ResolveResourceClass;
use Moox\Core\Traits\Tabs\HasListPageTabs;

abstract class ItemListPage extends ListRecords
{
    use HasListPageTabs;
    use ResolveResourceClass;

    public function mount(): void
    {
        parent::mount();
        $this->mountHasListPageTabs();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.simple-item.tabs', ItemResource::class);
    }
}
