<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticIncotermResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticIncotermResource;
use Moox\Data\Models\StaticIncoterm;

class ListStaticIncoterms extends BaseListStatic
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
