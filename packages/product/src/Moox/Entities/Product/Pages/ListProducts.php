<?php

declare(strict_types=1);

namespace Moox\Product\Moox\Entities\Product\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Product\Models\Product;

class ListProducts extends BaseListDrafts
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('product.resources.product.tabs', Product::class);
    }
}
