<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountriesStaticCurrenciesResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Data\Filament\Resources\StaticCountriesStaticCurrenciesResource;

class EditStaticCountriesStaticCurrencies extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = StaticCountriesStaticCurrenciesResource::class;
}
