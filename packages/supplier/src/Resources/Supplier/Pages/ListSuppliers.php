<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources\Supplier\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Supplier\Models\Supplier;
use Moox\Supplier\Resources\SupplierResource;

class ListSuppliers extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = SupplierResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('supplier.resources.supplier.tabs', Supplier::class);
    }
}
