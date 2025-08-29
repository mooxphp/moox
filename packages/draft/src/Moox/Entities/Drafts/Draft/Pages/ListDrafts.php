<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Draft\Models\Draft;

class ListDrafts extends BaseListDrafts
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('draft.resources.draft.tabs', Draft::class);
    }
}
