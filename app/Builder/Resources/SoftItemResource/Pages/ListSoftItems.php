<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftItemResource\Pages;

use App\Builder\Models\SoftItem;
use App\Builder\Resources\SoftItemResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Override;

class ListSoftItems extends ListRecords
{
    use BaseInListPage;
    use HasListPageTabs;
    use SingleSoftDeleteInListPage;

    protected static string $resource = SoftItemResource::class;

    #[Override]
    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.soft-item.tabs', SoftItem::class);
    }
}
