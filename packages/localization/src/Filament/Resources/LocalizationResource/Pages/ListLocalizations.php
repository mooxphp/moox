<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Localization\Filament\Resources\LocalizationResource;

class ListLocalizations extends BaseListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = LocalizationResource::class;
}
