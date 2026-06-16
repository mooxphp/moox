<?php

declare(strict_types=1);

namespace Moox\Staff\Resources\Staff\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Staff\Resources\StaffResource;

class EditStaff extends BaseEditRecord
{
    protected static string $resource = StaffResource::class;
}
