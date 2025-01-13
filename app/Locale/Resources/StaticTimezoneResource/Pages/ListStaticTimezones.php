<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticTimezoneResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListStaticTimezones extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = \App\Locale\Resources\StaticTimezoneResource::class;
}
