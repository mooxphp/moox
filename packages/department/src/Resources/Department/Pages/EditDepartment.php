<?php

declare(strict_types=1);

namespace Moox\Department\Resources\Department\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Department\Resources\DepartmentResource;

class EditDepartment extends BaseEditRecord
{
    protected static string $resource = DepartmentResource::class;
}
