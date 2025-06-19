<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountriesStaticCurrenciesResource\Pages;

use Moox\Data\Filament\Resources\StaticCountriesStaticCurrenciesResource;
use Moox\Data\Models\StaticCountriesStaticCurrencies;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListStaticCountriesStaticCurrencies extends ListRecords
{
    use BaseInListPage, HasListPageTabs, SingleSimpleInListPage;

    protected static string $resource = StaticCountriesStaticCurrenciesResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-countries-static-currencies.tabs', StaticCountriesStaticCurrencies::class);
    }
}
