<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiConnectionResource\Pages;

use Moox\Connect\Filament\Resources\ApiConnectionResource;
use Moox\Connect\Filament\Widgets\ConnectionTreeStatusWidget;
use Moox\Connect\Models\ApiConnection;
use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListApiConnections extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = ApiConnectionResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('api-connection.tabs', ApiConnection::class);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConnectionTreeStatusWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
