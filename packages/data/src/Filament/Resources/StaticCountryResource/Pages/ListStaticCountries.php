<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListStaticCountries extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = \Moox\Data\Filament\Resources\StaticCountryResource::class;
}
