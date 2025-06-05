<?php

namespace Moox\Packages\Moox\Entities\Packages\Package\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Packages\Moox\Entities\Packages\Package\PackagesResource;

class EditPackage extends EditRecord
{
    protected static string $resource = PackagesResource::class;
}
