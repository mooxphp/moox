<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Moox\Data\Filament\Resources\StaticLocaleResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditStaticLocale extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = StaticLocaleResource::class;
}
