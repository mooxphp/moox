<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources\StaticCurrencyResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListStaticCurrencies extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage, TabsInListPage;

    protected static string $resource = \Moox\DataLanguages\Resources\StaticCurrencyResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-currency.tabs', \Moox\DataLanguages\Models\StaticCurrency::class);
    }
}
