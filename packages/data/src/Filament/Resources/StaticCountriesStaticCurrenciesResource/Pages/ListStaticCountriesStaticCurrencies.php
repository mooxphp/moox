<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountriesStaticCurrenciesResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticCountriesStaticCurrenciesResource;
use Moox\Data\Models\StaticCountriesStaticCurrencies;

class ListStaticCountriesStaticCurrencies extends BaseListRecords
{
    use HasListPageTabs;

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
