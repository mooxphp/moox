<?php

namespace Moox\Attribute\Resources\AttributeValues\Pages;

use Moox\Attribute\Models\Attribute;
use Moox\Attribute\Resources\AttributeValuesResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListAttributeValues extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = AttributeValuesResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('attribute.resources.attribute.tabs', Attribute::class);
    }
}
