<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages;

use Moox\Address\Models\Address;
use Moox\Address\Resources\Address\Pages\Concerns\InitializesValidationBag;
use Moox\Address\Resources\AddressResource;
use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListAddresses extends BaseListRecords
{
    use HasListPageTabs;
    use InitializesValidationBag;

    protected static string $resource = AddressResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('address.resources.address.tabs', Address::class);
    }
}
