<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources\Supplier\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Supplier\Resources\SupplierResource;

class ListSuppliers extends BaseListRecords
{
    protected static string $resource = SupplierResource::class;
}
