<?php

declare(strict_types=1);

namespace Moox\Department\Resources\Department\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\Department\Resources\DepartmentResource;

class ViewDepartment extends BaseViewRecord
{
    protected static string $resource = DepartmentResource::class;
}
