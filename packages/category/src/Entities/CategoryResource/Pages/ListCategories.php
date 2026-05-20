<?php

declare(strict_types=1);

namespace Moox\Category\Entities\CategoryResource\Pages;

use Moox\Category\Entities\CategoryResource;
use Moox\Category\Models\Category;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListCategories extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = CategoryResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('category.resources.category.tabs', Category::class);
    }
}
