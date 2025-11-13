<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountryResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticCountryResource;

class CreateStaticCountry extends BaseCreateRecord
{
    protected static string $resource = StaticCountryResource::class;
}
