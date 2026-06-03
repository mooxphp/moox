<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiEndpointResource\Pages;

use Moox\Connect\Filament\Resources\ApiEndpointResource;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListApiEndpoints extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = ApiEndpointResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('api-endpoint.tabs', ApiEndpoint::class);
    }

    protected function getHeaderActions(): array
    {
        return parent::getHeaderActions();
    }
}
