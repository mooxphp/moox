<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiConnectionResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewApiConnection extends ViewRecord
{
    use BaseInViewPage, SingleSimpleInViewPage;

    protected static string $resource = \Moox\Connect\Filament\Resources\ApiConnectionResource::class;
}
