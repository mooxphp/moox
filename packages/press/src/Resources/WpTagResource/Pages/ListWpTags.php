<?php

namespace Moox\Press\Resources\WpTagResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\TabsInListPage;
use Moox\Press\Models\WpTag;
use Moox\Press\Resources\WpTagResource;

class ListWpTags extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = WpTagResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.tag.tabs', WpTag::class);
    }
}
