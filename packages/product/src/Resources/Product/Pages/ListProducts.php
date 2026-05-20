<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Product\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Product\Models\Product;
use Moox\Product\Resources\ProductResource;

class ListProducts extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = ProductResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('product.resources.product.tabs', Product::class);
    }
}
