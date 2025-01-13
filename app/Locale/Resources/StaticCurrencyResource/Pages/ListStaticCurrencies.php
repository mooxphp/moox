<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticCurrencyResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListStaticCurrencies extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage, TabsInListPage;

    protected static string $resource = \App\Locale\Resources\StaticCurrencyResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-currency.tabs', \App\Locale\Models\StaticCurrency::class);
    }
}
