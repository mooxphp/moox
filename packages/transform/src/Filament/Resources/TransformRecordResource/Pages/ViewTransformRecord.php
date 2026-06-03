<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformRecordResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Transform\Filament\Resources\TransformRecordResource;

class ViewTransformRecord extends ViewRecord
{
    use BaseInViewPage;
    use SingleSimpleInViewPage;

    protected static string $resource = TransformRecordResource::class;
}
