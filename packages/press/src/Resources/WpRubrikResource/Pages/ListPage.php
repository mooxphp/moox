<?php

namespace Moox\Press\Resources\WpRubrikResource\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Traits\HasDynamicTabs;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Models\WpPost;
use Moox\Press\Models\WpTerm;
use Moox\Press\Resources\WpRubrikResource;

class ListPage extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpRubrikResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.rubrik.tabs', WpTerm::class);
    }
}
