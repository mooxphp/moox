<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftItemResource\Pages;

use App\Builder\Resources\SoftItemResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInViewPage;

class ViewSoftItem extends ViewRecord
{
    use BaseInViewPage;
    use SingleSoftDeleteInViewPage;

    protected static string $resource = SoftItemResource::class;
}
