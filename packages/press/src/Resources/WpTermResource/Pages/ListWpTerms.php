<?php

namespace Moox\Press\Resources\WpTermResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\Press\Models\WpTerm;
use Moox\Press\Resources\WpTermResource;

class ListWpTerms extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = WpTermResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.term.tabs', WpTerm::class);
    }
}
