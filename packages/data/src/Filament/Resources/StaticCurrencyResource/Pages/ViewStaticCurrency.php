<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticCurrencyResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewStaticCurrency extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = \Moox\Data\Filament\Resources\StaticCurrencyResource::class;
}
