<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCountryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Data\Filament\Resources\StaticCountryResource;

class EditStaticCountry extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = StaticCountryResource::class;
}
