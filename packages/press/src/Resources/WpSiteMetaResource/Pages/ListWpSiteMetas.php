<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Press\Models\WpSiteMeta;
use Moox\Press\Resources\WpSiteMetaResource;

class ListWpSiteMetas extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpSiteMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.siteMeta.tabs', WpSiteMeta::class);
    }
}
