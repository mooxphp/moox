<?php

namespace Moox\Press\Resources\WpTagResource\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Press\Resources\WpTagResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Models\WpTag;

class ListWpTags extends ListRecords
{
    use HasDynamicTabs;

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
