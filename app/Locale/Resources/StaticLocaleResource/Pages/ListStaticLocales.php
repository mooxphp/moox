<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticLocaleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListStaticLocales extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage, TabsInListPage;

    protected static string $resource = \App\Locale\Resources\StaticLocaleResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-locale.tabs', \App\Locale\Models\StaticLocale::class);
    }
}
