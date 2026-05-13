<?php

namespace Moox\Attribute\Moox\Entities\AttributeValues\Pages;

use Moox\Attribute\Models\Attribute;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListAttributeValues extends BaseListDrafts
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('attribute.resources.attribute.tabs', Attribute::class);
    }
}
