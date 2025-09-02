<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountryResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\Data\Filament\Resources\StaticCountryResource;

class ViewStaticCountry extends BaseViewRecord
{
    protected static string $resource = StaticCountryResource::class;
}
