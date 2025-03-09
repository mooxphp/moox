<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Entities\Items\Item\MooxResource;
use Moox\Core\Traits\Tabs\TabsInListPage;

abstract class MooxListPage extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = MooxResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.simple-item.tabs', MooxResource::class);
    }
}
