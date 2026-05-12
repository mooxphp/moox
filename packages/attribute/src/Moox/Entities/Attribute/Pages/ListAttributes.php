<?php

namespace Moox\Attribute\Moox\Entities\Attribute\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Attribute\Models\Attribute;
class ListAttributes extends BaseListDrafts
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('attribute.resources.attribute.tabs', Attribute::class);
    }
}
