<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListApiLogs extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = \Moox\Connect\Filament\Resources\ApiLogResource::class;
}
