<?php

namespace Moox\Press\Resources\WpTermMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Press\Models\WpTermMeta;
use Moox\Press\Resources\WpTermMetaResource;

class ListWpTermMetas extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpTermMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.termMeta.tabs', WpTermMeta::class);
    }
}
