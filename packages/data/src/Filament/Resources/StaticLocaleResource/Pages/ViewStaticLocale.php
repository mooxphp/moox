<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Data\Filament\Resources\StaticLocaleResource;

class ViewStaticLocale extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = StaticLocaleResource::class;
}
