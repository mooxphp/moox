<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources\StaticCountriesStaticCurrenciesResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListStaticCountriesStaticCurrencies extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage, TabsInListPage;

    protected static string $resource = \Moox\DataLanguages\Resources\StaticCountriesStaticCurrenciesResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-countries-static-currencies.tabs', \Moox\DataLanguages\Models\StaticCountriesStaticCurrencies::class);
    }
}
