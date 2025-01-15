<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources\StaticCountriesStaticCurrenciesResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewStaticCountriesStaticCurrencies extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = \Moox\DataLanguages\Resources\StaticCountriesStaticCurrenciesResource::class;
}
