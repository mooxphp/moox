<?php

declare(strict_types=1);

namespace Moox\Department\Resources\Department\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Department\Resources\DepartmentResource;

class CreateDepartment extends BaseCreateRecord
{
    protected static string $resource = DepartmentResource::class;
}
