<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticIncotermResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\DataLegacy\Filament\Resources\StaticIncotermResource;
use Moox\DataLegacy\Models\StaticIncoterm;

class ListStaticIncoterms extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticIncotermResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-incoterm.tabs', StaticIncoterm::class);
    }
}
