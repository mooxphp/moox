<?php

declare(strict_types=1);

namespace Moox\Department\Resources\DepartmentPerson\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Department\Resources\DepartmentPersonResource;

class CreateDepartmentPerson extends CreateRecord
{
    protected static string $resource = DepartmentPersonResource::class;
}
