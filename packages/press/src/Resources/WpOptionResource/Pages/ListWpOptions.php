<?php

namespace Moox\Press\Resources\WpOptionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\Press\Models\WpOption;
use Moox\Press\Resources\WpOptionResource;

class ListWpOptions extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = WpOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.option.tabs', WpOption::class);
    }
}
