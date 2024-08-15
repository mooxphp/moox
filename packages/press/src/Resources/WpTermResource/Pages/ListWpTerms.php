<?php

namespace Moox\Press\Resources\WpTermResource\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Press\Resources\WpTermResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Models\WpTerm;

class ListWpTerms extends ListRecords
{
    use HasDynamicTabs;

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
