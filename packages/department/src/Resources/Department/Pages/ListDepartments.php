<?php

declare(strict_types=1);

namespace Moox\Department\Resources\Department\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Department\Models\Department;
use Moox\Department\Resources\DepartmentResource;

class ListDepartments extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = DepartmentResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('department.resources.department.tabs', Department::class);
    }
}
