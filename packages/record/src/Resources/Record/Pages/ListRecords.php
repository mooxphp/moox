<?php

namespace Moox\Record\Resources\Record\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Record\Models\Record;
use Moox\Record\Resources\RecordResource;

class ListRecords extends BaseListRecords
{
    use HasListPageTabs;
    
    protected static string $resource = RecordResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('record.resources.record.tabs', Record::class);
    }
}
