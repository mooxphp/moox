<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListLocalizations extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = \Moox\Localization\Filament\Resources\LocalizationResource::class;
}
