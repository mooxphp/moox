<?php

declare(strict_types=1);

namespace Moox\Staff\Resources\Staff\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Staff\Models\Staff;
use Moox\Staff\Resources\StaffResource;

class ListStaff extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaffResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('staff.resources.staff.tabs', Staff::class);
    }
}
