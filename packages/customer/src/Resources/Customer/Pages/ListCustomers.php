<?php

declare(strict_types=1);

namespace Moox\Customer\Resources\Customer\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Customer\Models\Customer;
use Moox\Customer\Resources\CustomerResource;

class ListCustomers extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = CustomerResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('customer.resources.customer.tabs', Customer::class);
    }
}
