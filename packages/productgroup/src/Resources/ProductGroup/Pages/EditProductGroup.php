<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\ProductGroup\Models\ProductGroup;
use Moox\ProductGroup\Resources\ProductGroupResource;

class EditProductGroup extends BaseEditDraft
{
    use HasListPageTabs;

    protected static string $resource = ProductGroupResource::class;

    public function getHeading(): string
    {
        return (string) ($this->record->title ?? parent::getHeading());
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('productgroup.resources.productgroup.tabs', ProductGroup::class);
    }
}
