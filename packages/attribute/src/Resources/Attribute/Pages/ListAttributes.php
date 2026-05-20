<?php

namespace Moox\Attribute\Resources\Attribute\Pages;

use Moox\Attribute\Models\Attribute;
use Moox\Attribute\Resources\AttributeResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListAttributes extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = AttributeResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('attribute.resources.attribute.tabs', Attribute::class);
    }
}
