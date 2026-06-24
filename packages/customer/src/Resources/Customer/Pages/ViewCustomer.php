<?php

declare(strict_types=1);

namespace Moox\Customer\Resources\Customer\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\Customer\Resources\CustomerResource;

class ViewCustomer extends BaseViewRecord
{
    protected static string $resource = CustomerResource::class;
}
