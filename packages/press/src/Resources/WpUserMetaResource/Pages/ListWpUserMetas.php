<?php

namespace Moox\Press\Resources\WpUserMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Press\Models\WpUserMeta;
use Moox\Press\Resources\WpUserMetaResource;

class ListWpUserMetas extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpUserMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.userMeta.tabs', WpUserMeta::class);
    }
}
