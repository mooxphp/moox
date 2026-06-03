<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformRecordResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Transform\Filament\Resources\TransformRecordResource;

class EditTransformRecord extends EditRecord
{
    use BaseInEditPage;
    use SingleSimpleInEditPage;

    protected static string $resource = TransformRecordResource::class;
}
