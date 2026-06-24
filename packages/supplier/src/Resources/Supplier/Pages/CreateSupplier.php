<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources\Supplier\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Supplier\Resources\SupplierResource;

class CreateSupplier extends BaseCreateRecord
{
    protected static string $resource = SupplierResource::class;
}
