<?php

namespace Moox\Press\Resources\WpPageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Press\Models\WpPage;
use Moox\Press\Resources\WpPageResource;

class ListWpPage extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpPageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.page.tabs', WpPage::class);
    }
}
