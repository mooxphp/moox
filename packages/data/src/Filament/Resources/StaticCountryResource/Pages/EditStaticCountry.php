<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountryResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Data\Filament\Resources\StaticCountryResource;

class EditStaticCountry extends BaseEditRecord
{
    protected static string $resource = StaticCountryResource::class;
}
