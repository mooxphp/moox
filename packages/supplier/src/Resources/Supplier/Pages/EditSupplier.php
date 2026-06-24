<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources\Supplier\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Supplier\Resources\SupplierResource;

class EditSupplier extends BaseEditRecord
{
    protected static string $resource = SupplierResource::class;
}
