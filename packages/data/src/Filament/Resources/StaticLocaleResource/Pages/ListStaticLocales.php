<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticLocaleResource;
use Moox\Data\Models\StaticLocale;

class ListStaticLocales extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticLocaleResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('entities.static-locale.tabs', StaticLocale::class);
    }
}
