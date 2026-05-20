<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Product\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Product\Models\Product;
use Moox\Product\Resources\ProductResource;

class EditProduct extends BaseEditDraft
{
    use HasListPageTabs;

    protected static string $resource = ProductResource::class;

    public function getHeading(): string
    {
        return (string) ($this->record->title ?? parent::getHeading());
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('product.resources.product.tabs', Product::class);
    }
}
