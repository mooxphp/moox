<?php

namespace Moox\Press\Resources\WpPostMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Press\Models\WpPostMeta;
use Moox\Press\Resources\WpPostMetaResource;

class ListWpPostMetas extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpPostMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.postMeta.tabs', WpPostMeta::class);
    }
}
