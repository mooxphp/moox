<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Entities\Items\Item\MooxResource;
use Moox\Core\Traits\Tabs\HasListPageTabs;

abstract class ItemListPage extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = MooxResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountHasListPageTabs();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.simple-item.tabs', MooxResource::class);
    }
}
