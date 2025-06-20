<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages;

use Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewStaticCountriesStaticTimezones extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = StaticCountriesStaticTimezonesResource::class;
}
