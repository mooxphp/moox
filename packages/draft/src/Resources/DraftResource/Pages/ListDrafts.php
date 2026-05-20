<?php

namespace Moox\Draft\Resources\DraftResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Draft\Models\Draft;
use Moox\Draft\Resources\DraftResource;

class ListDrafts extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = DraftResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('draft.resources.draft.tabs', Draft::class);
    }
}
