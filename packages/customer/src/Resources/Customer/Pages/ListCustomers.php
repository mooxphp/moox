<?php

declare(strict_types=1);

namespace Moox\Customer\Resources\Customer\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Customer\Resources\CustomerResource;

class ListCustomers extends BaseListRecords
{
    protected static string $resource = CustomerResource::class;
}
