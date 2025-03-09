<?php

namespace Moox\Item\Moox\Entities\Items\Item\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Entities\Items\Item\Pages\ItemListPage;
use Moox\Item\Moox\Entities\Items\Item\ItemResource;

class ListPage extends ItemListPage
{
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
