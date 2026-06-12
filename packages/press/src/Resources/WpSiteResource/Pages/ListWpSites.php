<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Press\Models\WpSite;
use Moox\Press\Resources\WpSiteResource;

class ListWpSites extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.site.tabs', WpSite::class);
    }
}
