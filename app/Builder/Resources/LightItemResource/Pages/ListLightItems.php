<?php

declare(strict_types=1);

namespace App\Builder\Resources\LightItemResource\Pages;

use App\Builder\Resources\LightItemResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListLightItems extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = LightItemResource::class;
}
