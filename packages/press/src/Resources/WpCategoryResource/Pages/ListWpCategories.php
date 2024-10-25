<?php

namespace Moox\Press\Resources\WpCategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\TabsInPage;
use Moox\Press\Models\WpCategory;
use Moox\Press\Resources\WpCategoryResource;

class ListWpCategories extends ListRecords
{
    use TabsInPage;

    protected static string $resource = WpCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.category.tabs', WpCategory::class);
    }
}
