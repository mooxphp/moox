<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticUnitResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticUnitResource;
use Moox\Data\Models\StaticUnit;

class ListStaticUnits extends BaseListStatic
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
