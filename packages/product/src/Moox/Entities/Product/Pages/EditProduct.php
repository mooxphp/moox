<?php

declare(strict_types=1);

namespace Moox\Product\Moox\Entities\Product\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Product\Models\Product;

class EditProduct extends BaseEditDraft
{
    use HasListPageTabs;

    public function getHeading(): string
    {
        return (string) ($this->record->title ?? parent::getHeading());
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('product.resources.product.tabs', Product::class);
    }
}
