<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticVatCategoryResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\DataLegacy\Filament\Resources\StaticVatCategoryResource;
use Moox\DataLegacy\Models\StaticVatCategory;

class ListStaticVatCategories extends BaseListRecords
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
