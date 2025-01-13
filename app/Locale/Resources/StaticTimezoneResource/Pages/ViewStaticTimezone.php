<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticTimezoneResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewStaticTimezone extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = \App\Locale\Resources\StaticTimezoneResource::class;
}
