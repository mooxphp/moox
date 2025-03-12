<?php

declare(strict_types=1);

namespace App\Builder\Resources\FullItemResource\Pages;

use App\Builder\Resources\FullItemResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewFullItem extends ViewRecord
{
    use BaseInViewPage;
    use SingleSimpleInViewPage;

    protected static string $resource = FullItemResource::class;
}
