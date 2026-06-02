<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformRecordResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Transform\Filament\Resources\TransformRecordResource;
use Moox\Transform\Models\TransformRecord;

class ListTransformRecords extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = TransformRecordResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('transform-record.tabs', TransformRecord::class);
    }
}
