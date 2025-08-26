<?php

namespace Moox\Record\Moox\Entities\Records\Record\Pages;

use Moox\Record\Models\Record;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;

class ListRecords extends BaseListRecords
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('record.resources.record.tabs', Record::class);
    }
}
