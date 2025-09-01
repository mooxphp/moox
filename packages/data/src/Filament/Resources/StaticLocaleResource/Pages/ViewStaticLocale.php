<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Data\Filament\Resources\StaticLocaleResource;
use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;

class ViewStaticLocale extends BaseViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = StaticLocaleResource::class;
}
