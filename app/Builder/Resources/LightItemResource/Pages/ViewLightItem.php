<?php

declare(strict_types=1);

namespace App\Builder\Resources\LightItemResource\Pages;

use App\Builder\Resources\LightItemResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewLightItem extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = LightItemResource::class;
}
