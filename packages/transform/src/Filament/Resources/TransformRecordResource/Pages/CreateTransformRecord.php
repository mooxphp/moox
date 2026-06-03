<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformRecordResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;
use Moox\Transform\Filament\Resources\TransformRecordResource;

class CreateTransformRecord extends CreateRecord
{
    use BaseInCreatePage;
    use SingleSimpleInCreatePage;

    protected static string $resource = TransformRecordResource::class;
}
