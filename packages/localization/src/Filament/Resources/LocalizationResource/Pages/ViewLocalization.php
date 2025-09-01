<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Localization\Filament\Resources\LocalizationResource;

class ViewLocalization extends BaseViewRecord
{
    protected static string $resource = LocalizationResource::class;
}
