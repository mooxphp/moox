<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Address\Resources\AddressResource;

class CreateAddress extends CreateRecord
{
    protected static string $resource = AddressResource::class;
}
