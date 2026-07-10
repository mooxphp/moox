<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticVatCategoryResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticVatCategoryResource;
use Moox\Data\Models\StaticVatCategory;

class ListStaticVatCategories extends BaseListStatic
{
    use HasListPageTabs;

    protected static string $resource = StaticVatCategoryResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-vat-category.tabs', StaticVatCategory::class);
    }
}
