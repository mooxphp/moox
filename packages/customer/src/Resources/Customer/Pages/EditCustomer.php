<?php

declare(strict_types=1);

namespace Moox\Customer\Resources\Customer\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Customer\Resources\CustomerResource;

class EditCustomer extends BaseEditRecord
{
    protected static string $resource = CustomerResource::class;
}
