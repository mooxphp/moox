<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsageType\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Address\Resources\AddressUsageTypeResource;

class CreateAddressUsageType extends CreateRecord
{
    protected static string $resource = AddressUsageTypeResource::class;
}
