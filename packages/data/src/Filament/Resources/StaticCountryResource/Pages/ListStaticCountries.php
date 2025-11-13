<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountryResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Data\Filament\Resources\StaticCountryResource;

class ListStaticCountries extends BaseListRecords
{
    protected static string $resource = StaticCountryResource::class;
}
