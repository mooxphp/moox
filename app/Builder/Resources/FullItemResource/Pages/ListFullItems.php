<?php

declare(strict_types=1);

namespace App\Builder\Resources\FullItemResource\Pages;

use App\Builder\Models\FullItem;
use App\Builder\Resources\FullItemResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Override;

class ListFullItems extends ListRecords
{
    use BaseInListPage;
    use HasListPageTabs;
    use SingleSimpleInListPage;

    protected static string $resource = FullItemResource::class;

    #[Override]
    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.full-item.tabs', FullItem::class);
    }
}
