<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCurrencyResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListStaticCurrencies extends ListRecords
{
    use BaseInListPage, HasListPageTabs, SingleSimpleInListPage;

    protected static string $resource = \Moox\Data\Filament\Resources\StaticCurrencyResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-currency.tabs', \Moox\Data\Models\StaticCurrency::class);
    }
}
