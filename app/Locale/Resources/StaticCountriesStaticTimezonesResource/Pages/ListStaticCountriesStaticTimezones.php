<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticCountriesStaticTimezonesResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListStaticCountriesStaticTimezones extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = \App\Locale\Resources\StaticCountriesStaticTimezonesResource::class;
}
