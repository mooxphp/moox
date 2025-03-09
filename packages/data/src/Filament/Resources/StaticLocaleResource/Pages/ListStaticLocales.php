<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListStaticLocales extends ListRecords
{
    use BaseInListPage, HasListPageTabs, SingleSimpleInListPage;

    protected static string $resource = \Moox\Data\Filament\Resources\StaticLocaleResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountHasListPageTabs();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-locale.tabs', \Moox\Data\Models\StaticLocale::class);
    }
}
