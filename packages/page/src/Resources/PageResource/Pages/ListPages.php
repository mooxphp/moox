<?php

namespace Moox\Page\Resources\PageResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Page\Resources\PageResource;
use Moox\Page\Support\PageModels;

class ListPages extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = PageResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('page.resources.page.tabs', PageModels::page());
    }
}
