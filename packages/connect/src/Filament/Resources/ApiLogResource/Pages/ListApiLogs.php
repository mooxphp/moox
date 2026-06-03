<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiLogResource\Pages;

use Moox\Connect\Filament\Resources\ApiLogResource;
use Moox\Connect\Models\ApiLog;
use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListApiLogs extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = ApiLogResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('api-log.tabs', ApiLog::class);
    }
}
