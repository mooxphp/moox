<?php

declare(strict_types=1);

namespace Moox\Staff\Resources\Staff\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Staff\Resources\StaffResource;

class CreateStaff extends BaseCreateRecord
{
    protected static string $resource = StaffResource::class;
}
