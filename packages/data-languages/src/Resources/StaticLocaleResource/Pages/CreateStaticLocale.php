<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources\StaticLocaleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateStaticLocale extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = \Moox\DataLanguages\Resources\StaticLocaleResource::class;
}
