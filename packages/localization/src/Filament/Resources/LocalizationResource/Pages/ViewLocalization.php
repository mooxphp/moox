<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Localization\Filament\Resources\LocalizationResource;

class ViewLocalization extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = LocalizationResource::class;
}
