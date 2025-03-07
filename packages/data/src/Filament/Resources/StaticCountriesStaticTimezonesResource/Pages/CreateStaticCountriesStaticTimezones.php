<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateStaticCountriesStaticTimezones extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = \Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource::class;
}
