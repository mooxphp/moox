<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticTimezoneResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;
use Moox\Data\Filament\Resources\StaticTimezoneResource;

class ListStaticTimezones extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = StaticTimezoneResource::class;
}
