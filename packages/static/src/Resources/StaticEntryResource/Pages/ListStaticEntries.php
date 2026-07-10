<?php

declare(strict_types=1);

namespace Moox\Static\Resources\StaticEntryResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Static\Models\StaticEntry;
use Moox\Static\Resources\StaticEntryResource;

class ListStaticEntries extends BaseListStatic
{
    use HasListPageTabs;

    protected static string $resource = StaticEntryResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static.resources.static_entry.tabs', StaticEntry::class);
    }
}
