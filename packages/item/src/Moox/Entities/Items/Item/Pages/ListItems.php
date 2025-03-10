<?php

namespace Moox\Item\Moox\Entities\Items\Item\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Item\Moox\Entities\Items\Item\ItemResource;

class ListItems extends BaseListItems
{
    use HasListPageTabs;

    public function mount(): void
    {
        parent::mount();
        $this->mountHasListPageTabs();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.simple-item.tabs', ItemResource::class);
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
