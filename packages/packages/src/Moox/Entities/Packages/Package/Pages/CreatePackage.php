<?php

namespace Moox\Packages\Moox\Entities\Packages\Package\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Packages\Moox\Entities\Packages\Package\PackagesResource;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackagesResource::class;
}
