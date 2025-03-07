<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditStaticCountriesStaticTimezones extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = \Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource::class;
}
