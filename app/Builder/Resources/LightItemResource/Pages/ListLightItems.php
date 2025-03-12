<?php

declare(strict_types=1);

namespace App\Builder\Resources\LightItemResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListLightItems extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = \App\Builder\Resources\LightItemResource::class;
}
