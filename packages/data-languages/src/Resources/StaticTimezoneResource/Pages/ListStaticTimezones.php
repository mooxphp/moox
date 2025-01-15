<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources\StaticTimezoneResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListStaticTimezones extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = \Moox\DataLanguages\Resources\StaticTimezoneResource::class;
}
