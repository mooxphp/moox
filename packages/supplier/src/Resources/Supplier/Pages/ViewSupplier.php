<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources\Supplier\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\Supplier\Resources\SupplierResource;

class ViewSupplier extends BaseViewRecord
{
    protected static string $resource = SupplierResource::class;
}
