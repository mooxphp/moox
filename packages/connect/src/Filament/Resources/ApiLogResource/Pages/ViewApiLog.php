<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiLogResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Connect\Filament\Resources\ApiLogResource;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewApiLog extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = ApiLogResource::class;
}
