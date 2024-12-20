<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticCountryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateStaticCountry extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = \App\Locale\Resources\StaticCountryResource::class;
}
