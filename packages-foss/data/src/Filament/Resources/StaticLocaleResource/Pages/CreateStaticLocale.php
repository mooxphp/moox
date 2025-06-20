<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Moox\Data\Filament\Resources\StaticLocaleResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateStaticLocale extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = StaticLocaleResource::class;
}
