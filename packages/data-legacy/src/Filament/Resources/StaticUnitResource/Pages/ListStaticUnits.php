<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticUnitResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\DataLegacy\Filament\Resources\StaticUnitResource;
use Moox\DataLegacy\Models\StaticUnit;

class ListStaticUnits extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticUnitResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-unit.tabs', StaticUnit::class);
    }
}
