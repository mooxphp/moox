<?php

declare(strict_types=1);

namespace App\Builder\Resources\SimpleItemResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListSimpleItems extends ListRecords
{
    use BaseInListPage, HasListPageTabs, SingleSimpleInListPage;

    protected static string $resource = \App\Builder\Resources\SimpleItemResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.simple-item.tabs', \App\Builder\Models\SimpleItem::class);
    }
}
