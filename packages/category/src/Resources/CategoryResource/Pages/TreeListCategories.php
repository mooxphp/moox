<?php

declare(strict_types=1);

namespace Moox\Category\Resources\CategoryResource\Pages;

use Moox\Category\Models\Category;
use Moox\Category\Resources\CategoryTreeResource;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeListCategories extends TreeIndexListRecords
{
    use HasListPageTabs;

    protected static string $resource = CategoryTreeResource::class;

    public function mount(): void
    {
        if (! request()->has('tab')) {
            $this->redirect(CategoryTreeResource::getUrl('index', ['tab' => 'all']));
        }

        parent::mount();

        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('category.resources.category.tabs', Category::class);
    }

    public function updatedActiveTab(): void
    {
        static::getResource()::setCurrentTab($this->activeTab);
        $this->tableFilters = null;
        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
        $this->resetTable();
        $this->refreshTreeIndexConfiguration();
    }
}
