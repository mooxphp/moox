<?php

declare(strict_types=1);

namespace App\Builder\Resources\TestItemResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewTestItem extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = \App\Builder\Resources\TestItemResource::class;
}
