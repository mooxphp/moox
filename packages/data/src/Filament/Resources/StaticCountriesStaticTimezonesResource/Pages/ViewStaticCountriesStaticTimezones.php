<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource;

class ViewStaticCountriesStaticTimezones extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = StaticCountriesStaticTimezonesResource::class;
}
