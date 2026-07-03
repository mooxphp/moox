<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\ProductGroup\Models\ProductGroup;
use Moox\ProductGroup\Resources\ProductGroupResource;

class ListProductGroups extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = ProductGroupResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('productgroup.resources.productgroup.tabs', ProductGroup::class);
    }
}
