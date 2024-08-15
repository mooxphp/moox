<?php

namespace Moox\Press\Resources\WpTermTaxonomyResource\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Traits\HasDynamicTabs;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Models\WpTermTaxonomy;
use Moox\Press\Resources\WpTermTaxonomyResource;

class ListWpTermTaxonomies extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpTermTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.termTaxonomy.tabs', WpTermTaxonomy::class);
    }
}
