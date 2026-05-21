<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages;

use Moox\Address\Resources\Address\Pages\Concerns\HandlesDuplicateAddressValidation;
use Moox\Address\Resources\Address\Pages\Concerns\InitializesValidationBag;
use Moox\Address\Resources\AddressResource;
use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;

class CreateAddress extends BaseCreateRecord
{
    use HandlesDuplicateAddressValidation;
    use InitializesValidationBag;

    protected static string $resource = AddressResource::class;
}
