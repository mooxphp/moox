<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftDeleteItemResource\Pages;

use App\Builder\Resources\SoftDeleteItemResource;
use App\Builder\Models\SoftDeleteItem;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListSoftDeleteItems extends ListRecords
{
    use BaseInListPage, HasListPageTabs, SingleSoftDeleteInListPage;

    protected static string $resource = SoftDeleteItemResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.soft-delete-item.tabs', SoftDeleteItem::class);
    }
}
