<?php

declare(strict_types=1);

namespace Moox\Staff\Resources\Staff\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\Staff\Resources\StaffResource;

class ViewStaff extends BaseViewRecord
{
    protected static string $resource = StaffResource::class;
}
