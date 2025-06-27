<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticTimezoneResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Data\Filament\Resources\StaticTimezoneResource;

class ViewStaticTimezone extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = StaticTimezoneResource::class;
}
